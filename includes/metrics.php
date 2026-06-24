<?php

declare(strict_types=1);

function ms_extract_port_from_address(string $address): ?int
{
    $address = trim(str_replace('::ffff:', '', $address));

    if (preg_match('/:(\d+)$/', $address, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

function ms_is_inbound_connection(string $localAddress): bool
{
    $localPort = ms_extract_port_from_address($localAddress);

    // Ports éphémères locaux = connexions sortantes du serveur.
    return $localPort === null || $localPort < 32768;
}
function ms_is_local_ip(string $ip): bool
{
    if ($ip === '' || $ip === '*' || $ip === '0.0.0.0') {
        return true;
    }

    if ($ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }

    if (str_starts_with($ip, '127.') || str_starts_with($ip, '10.')) {
        return true;
    }

    if (str_starts_with($ip, '192.168.') || str_starts_with($ip, '169.254.')) {
        return true;
    }

    return (bool) preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip);
}

function ms_extract_ip_from_address(string $address): ?string
{
    $address = trim(str_replace('::ffff:', '', $address));

    if ($address === '' || $address === '*') {
        return null;
    }

    if (str_starts_with($address, '[')) {
        if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $address, $matches)) {
            $ip = $matches[1];
            return ms_is_local_ip($ip) ? null : $ip;
        }
        return null;
    }

    if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})(?::\d+)?$/', $address, $matches)) {
        $ip = $matches[1];
        return ms_is_local_ip($ip) ? null : $ip;
    }

    if (preg_match('/^([0-9a-fA-F:]+)(?:\.\d+)?(?::\d+)?$/', $address, $matches)) {
        $ip = $matches[1];
        return ms_is_local_ip($ip) ? null : $ip;
    }

    return null;
}

function ms_parse_shell_users(?string $top = null): int
{
    if (is_string($top) && preg_match('/(\d+)\s+users?,/i', $top, $matches)) {
        return (int) $matches[1];
    }

    $w = ms_shell('w -h 2>/dev/null');
    if ($w !== '') {
        return count(array_filter(explode("\n", trim($w))));
    }

    $who = ms_shell('who 2>/dev/null');
    if ($who === '') {
        return 0;
    }

    return count(array_filter(explode("\n", trim($who))));
}

function ms_parse_netstat_connections(?string $netstatOutput = null): array
{
    $output = $netstatOutput ?? ms_shell('netstat -nt 2>/dev/null');
    $byIp = [];
    $established = 0;
    $establishedInbound = 0;

    if ($output !== '') {
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'Active Internet connections') !== false) {
                continue;
            }

            if (!preg_match('/^tcp/i', $line)) {
                continue;
            }

            $normalized = preg_replace('/\s+/', ' ', $line) ?? $line;
            $parts = explode(' ', $normalized);
            if (count($parts) < 6) {
                continue;
            }

            $state = strtoupper($parts[5]);
            if ($state !== 'ESTABLISHED') {
                continue;
            }

            $established++;
            if (!ms_is_inbound_connection($parts[3])) {
                continue;
            }

            $establishedInbound++;
            $ip = ms_extract_ip_from_address($parts[4]);
            if ($ip !== null) {
                $byIp[$ip] = ($byIp[$ip] ?? 0) + 1;
            }
        }
    }

    if ($established === 0) {
        $ssOutput = ms_shell("ss -H -nt state established 2>/dev/null");
        foreach (explode("\n", $ssOutput) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line) ?: [];
            if (count($parts) < 4) {
                continue;
            }

            $established++;
            if (!ms_is_inbound_connection($parts[2])) {
                continue;
            }

            $establishedInbound++;
            $ip = ms_extract_ip_from_address($parts[3]);
            if ($ip !== null) {
                $byIp[$ip] = ($byIp[$ip] ?? 0) + 1;
            }
        }
    }

    arsort($byIp);

    return [
        'unique_ips' => count($byIp),
        'established_tcp' => $established,
        'established_inbound' => $establishedInbound,
        'by_ip' => $byIp,
    ];
}

function ms_count_connected_clients(?string $netstatOutput = null, ?string $top = null): array
{
    $connections = ms_parse_netstat_connections($netstatOutput);

    return [
        'unique_ips' => (int) $connections['unique_ips'],
        'established_tcp' => (int) $connections['established_tcp'],
        'established_inbound' => (int) ($connections['established_inbound'] ?? 0),
        'shell_users' => ms_parse_shell_users($top),
        'by_ip' => $connections['by_ip'],
    ];
}

/** @deprecated Use ms_count_connected_clients()['unique_ips'] */
function ms_parse_connected_users(?string $top = null): int
{
    return ms_count_connected_clients(null, $top)['unique_ips'];
}

function ms_format_connections_report(array $stats): string
{
    $text = "Clients connectés (toutes origines, tous sites/comptes)\n";
    $text .= "=======================================================\n";
    $text .= 'IP uniques distantes : ' . $stats['unique_ips'] . "\n";
    $text .= 'Connexions TCP établies (total) : ' . $stats['established_tcp'] . "\n";
    $text .= 'Connexions entrantes (clients → serveur) : ' . ($stats['established_inbound'] ?? $stats['unique_ips']) . "\n";
    $text .= 'Sessions shell (SSH) : ' . $stats['shell_users'] . "\n\n";
    $text .= "Détail par adresse IP (connexions établies) :\n";
    $text .= str_repeat('-', 55) . "\n";

    if ($stats['by_ip'] === []) {
        $text .= "(aucune IP distante détectée)\n";
        return $text;
    }

    foreach ($stats['by_ip'] as $ip => $count) {
        $text .= sprintf("%-40s %4d conn.\n", $ip, $count);
    }

    return $text;
}

function ms_should_collect_on_dashboard(array $config): bool
{
    if (empty($config['cron_enabled'])) {
        return true;
    }

    if (!empty($config['cron_collect_on_dashboard'])) {
        return true;
    }

    $history = ms_load_history($config);
    if ($history === []) {
        return true;
    }

    $last = $history[array_key_last($history)];
    $interval = max(15, (int) ($config['history_interval'] ?? 60));
    $age = time() - (int) ($last['ts'] ?? 0);

    return $age > ($interval * 3);
}

function ms_history_dir(array $config): string
{
    return rtrim((string) ($config['history_path'] ?? MEGASTATS_ROOT . '/storage/metrics'), '/\\');
}

function ms_history_path(array $config): string
{
    return ms_history_dir($config) . '/history.json';
}

function ms_php_process_user(): string
{
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $info = posix_getpwuid(posix_geteuid());
        if (is_array($info) && !empty($info['name'])) {
            return (string) $info['name'];
        }
    }

    $user = get_current_user();
    return $user !== '' ? $user : 'unknown';
}

function ms_history_writability_report(array $config): array
{
    $dir = ms_history_dir($config);
    $file = ms_history_path($config);
    ms_ensure_log_dir($dir);

    $report = [
        'writable' => false,
        'path' => $dir,
        'file' => $file,
        'php_user' => ms_php_process_user(),
        'reason' => '',
    ];

    if (!is_dir($dir)) {
        $report['reason'] = 'Le dossier n\'existe pas et PHP n\'a pas pu le créer.';
        return $report;
    }

    $probe = $dir . '/.write_probe_' . getmypid();
    $probeOk = @file_put_contents($probe, (string) time(), LOCK_EX) !== false;
    if ($probeOk) {
        @unlink($probe);
    }

    if (!$probeOk) {
        $report['reason'] = sprintf(
            'PHP (%s) ne peut pas écrire dans %s. Vérifiez propriétaire, groupe (775) et SELinux.',
            $report['php_user'],
            $dir
        );
        return $report;
    }

    if (is_file($file) && !is_writable($file)) {
        $report['reason'] = sprintf(
            '%s existe mais n\'est pas modifiable par PHP (%s). Souvent : cron lancé en root.',
            basename($file),
            $report['php_user']
        );
        return $report;
    }

    $payload = is_file($file) ? (string) file_get_contents($file) : '[]';
    if (@file_put_contents($file, $payload, LOCK_EX) === false) {
        $report['reason'] = sprintf(
            'Impossible d\'écrire %s avec l\'utilisateur PHP %s.',
            basename($file),
            $report['php_user']
        );
        return $report;
    }

    $report['writable'] = true;
    return $report;
}

function ms_history_is_writable(array $config): bool
{
    return ms_history_writability_report($config)['writable'];
}

function ms_collect_and_record_metrics(array $config, bool $force = false): array
{
    $top = ms_shell_cached($config, 'top', (string) $config['top_com']);
    $dfFull = ms_shell_cached($config, 'df', (string) $config['df_com']);
    $freeOutput = ms_shell_cached($config, 'free', 'free -b');

    $vnstatOutput = '';
    if ((int) ($config['vnstat'] ?? 0) === 1) {
        exec('which vnstat', $output, $return);
        if ($return === 0) {
            $vnstatOutput = ms_shell_cached($config, 'vnstat', (string) $config['vnstat_com']);
        }
    }

    $disks = ms_build_disks($dfFull);
    $loads = ms_build_load($top);
    $summary = $vnstatOutput !== '' ? ms_parse_vnstat_summary($vnstatOutput) : [];
    $networkTodayMb = $summary['today_mb'] ?? null;
    $netstatRaw = ms_shell_cached($config, 'netstat', (string) $config['netstat_com']);
    $clientStats = ms_count_connected_clients($netstatRaw, $top);
    $connectedUsers = $clientStats['unique_ips'];

    $snapshot = ms_collect_snapshot(
        $config,
        $top,
        $loads,
        $disks,
        $networkTodayMb !== null ? (float) $networkTodayMb : null,
        $connectedUsers,
        $freeOutput
    );

    $recorded = ms_record_history($config, $snapshot, $force);

    return [
        'snapshot' => $snapshot,
        'recorded' => $recorded,
        'connected_users' => $connectedUsers,
        'client_stats' => $clientStats,
        'history_points' => count(ms_load_history($config)),
        'writable' => ms_history_is_writable($config),
    ];
}

function ms_parse_cpu_usage(string $top): ?float
{
    if (preg_match('/%Cpu\(s\):[^\n]+/i', $top, $lineMatch)) {
        $line = $lineMatch[0];
        $used = 0.0;

        if (preg_match('/([\d.]+)\s*us/i', $line, $us)) {
            $used += (float) $us[1];
        }
        if (preg_match('/([\d.]+)\s*sy/i', $line, $sy)) {
            $used += (float) $sy[1];
        }
        if ($used > 0) {
            return round(min($used, 100.0), 1);
        }
        if (preg_match('/([\d.]+)\s*id/i', $line, $idle)) {
            return round(max(0.0, 100.0 - (float) $idle[1]), 1);
        }
    }

    return null;
}

function ms_parse_memory_metrics(?string $freeOutput = null): array
{
    $free = $freeOutput ?? ms_shell('free -b');
    $result = [
        'mem_used_pct' => null,
        'mem_free_mb' => null,
        'mem_used_mb' => null,
        'swap_used_mb' => null,
    ];

    if (preg_match('/^Mem:\s+(\d+)\s+(\d+)\s+(\d+)/m', $free, $mem)) {
        $total = (float) $mem[1];
        $used = (float) $mem[2];
        $freeBytes = (float) $mem[3];

        $result['mem_used_pct'] = $total > 0 ? round($used / $total * 100, 1) : 0.0;
        $result['mem_free_mb'] = round($freeBytes / 1048576, 1);
        $result['mem_used_mb'] = round($used / 1048576, 1);
    }

    if (preg_match('/^Swap:\s+(\d+)\s+(\d+)/m', $free, $swap)) {
        $result['swap_used_mb'] = round(((float) $swap[2]) / 1048576, 1);
    }

    return $result;
}

function ms_parse_vnstat_summary(string $vnstatOutput): array
{
    $summary = [
        'today_mb' => null,
        'yesterday_mb' => null,
        'month_mb' => null,
        'total_mb' => null,
    ];

    if (preg_match('/^.*\btoday\b.*$/mi', $vnstatOutput, $todayLine)) {
        $parts = explode('|', $todayLine[0]);
        $summary['today_mb'] = round(ms_parse_vnstat_today_mb($parts[2] ?? ''));
    }

    if (preg_match('/^.*\byesterday\b.*$/mi', $vnstatOutput, $yesterdayLine)) {
        $parts = explode('|', $yesterdayLine[0]);
        $summary['yesterday_mb'] = round(ms_parse_vnstat_today_mb($parts[2] ?? ''));
    }

    if (preg_match('/^.*\bcurrent month\b.*$/mi', $vnstatOutput, $monthLine)) {
        $parts = explode('|', $monthLine[0]);
        $summary['month_mb'] = round(ms_parse_vnstat_today_mb($parts[2] ?? ''));
    }

    if (preg_match('/^.*\btotal\b.*$/mi', $vnstatOutput, $totalLine)) {
        $parts = explode('|', $totalLine[0]);
        $summary['total_mb'] = round(ms_parse_vnstat_today_mb($parts[2] ?? ''));
    }

    return $summary;
}

function ms_format_traffic_mb(?float $mb): string
{
    if ($mb === null) {
        return '—';
    }

    if ($mb >= 1048576) {
        return round($mb / 1048576, 2) . ' TiB';
    }
    if ($mb >= 1024) {
        return round($mb / 1024, 2) . ' GiB';
    }

    return round($mb, 1) . ' MiB';
}

function ms_load_history(array $config): array
{
    $path = ms_history_path($config);

    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ms_save_history(array $config, array $history): void
{
    $path = ms_history_path($config);
    ms_ensure_log_dir(dirname($path));
    file_put_contents($path, json_encode(array_values($history), JSON_PRETTY_PRINT), LOCK_EX);
}

function ms_record_history(array $config, array $snapshot, bool $force = false): bool
{
    $interval = max(15, (int) ($config['history_interval'] ?? 60));
    $maxPoints = max(20, (int) ($config['history_max_points'] ?? 120));
    $history = ms_load_history($config);
    $last = $history !== [] ? $history[array_key_last($history)] : null;

    if (
        !$force
        && is_array($last)
        && (($snapshot['ts'] ?? 0) - ($last['ts'] ?? 0)) < $interval
    ) {
        return false;
    }

    if (!ms_history_is_writable($config)) {
        ms_log($config, 'error', 'History directory is not writable: ' . dirname(ms_history_path($config)));
        return false;
    }

    $history[] = $snapshot;

    if (count($history) > $maxPoints) {
        $history = array_slice($history, -$maxPoints);
    }

    ms_save_history($config, $history);

    return true;
}

function ms_collect_snapshot(
    array $config,
    string $top,
    array $loads,
    array $disks,
    ?float $networkTodayMb,
    ?int $connectedUsers = null,
    ?string $freeOutput = null
): array {
    $mem = ms_parse_memory_metrics($freeOutput);
    $cpu = ms_parse_cpu_usage($top);
    $diskMax = 0;

    foreach ($disks as $disk) {
        $diskMax = max($diskMax, (int) ($disk['percent'] ?? 0));
    }

    if ($connectedUsers === null) {
        $connectedUsers = ms_parse_connected_users($top);
    }

    return [
        'ts' => time(),
        'cpu' => $cpu,
        'mem_used_pct' => $mem['mem_used_pct'],
        'mem_free_mb' => $mem['mem_free_mb'],
        'swap_used_mb' => $mem['swap_used_mb'],
        'load1' => $loads[0]['value'] ?? 0.0,
        'load5' => $loads[1]['value'] ?? 0.0,
        'load15' => $loads[2]['value'] ?? 0.0,
        'disk_max_pct' => $diskMax,
        'network_today_mb' => $networkTodayMb,
        'connected_users' => $connectedUsers,
        'disks' => array_map(
            static fn (array $disk): array => [
                'mount' => $disk['mount'],
                'percent' => (int) $disk['percent'],
            ],
            $disks
        ),
    ];
}

function ms_downsample_history(array $history, int $maxPoints): array
{
    $count = count($history);
    if ($count <= $maxPoints || $maxPoints < 2) {
        return $history;
    }

    $step = $count / $maxPoints;
    $sampled = [];
    for ($i = 0; $i < $maxPoints; $i++) {
        $index = (int) floor($i * $step);
        $sampled[] = $history[$index];
    }

    return $sampled;
}

function ms_history_range_bounds(string $range, ?int $fromTs, ?int $toTs): array
{
    $now = time();

    return match ($range) {
        '1w' => [$now - 604800, $now],
        '1m' => [$now - 2592000, $now],
        'custom' => [
            $fromTs ?? ($now - 86400),
            $toTs ?? $now,
        ],
        default => [$now - 86400, $now],
    };
}

function ms_filter_history_by_range(array $history, string $range, ?int $fromTs = null, ?int $toTs = null): array
{
    [$start, $end] = ms_history_range_bounds($range, $fromTs, $toTs);
    if ($start > $end) {
        [$start, $end] = [$end, $start];
    }

    return array_values(array_filter(
        $history,
        static fn (array $point): bool => ($point['ts'] ?? 0) >= $start && ($point['ts'] ?? 0) <= $end
    ));
}

function ms_format_history_labels(array $history, string $range): array
{
    $useDate = in_array($range, ['1w', '1m', 'custom'], true);

    return array_map(
        static function (array $point) use ($useDate): string {
            $ts = (int) ($point['ts'] ?? time());

            return $useDate ? date('d/m H:i', $ts) : date('H:i', $ts);
        },
        $history
    );
}

function ms_build_disk_history_series(array $history): array
{
    $mounts = [];
    foreach ($history as $point) {
        foreach ($point['disks'] ?? [] as $disk) {
            $mounts[$disk['mount'] ?? ''] = true;
        }
    }

    $series = [];
    foreach (array_keys($mounts) as $mount) {
        if ($mount === '') {
            continue;
        }
        $values = [];
        foreach ($history as $point) {
            $found = null;
            foreach ($point['disks'] ?? [] as $disk) {
                if (($disk['mount'] ?? '') === $mount) {
                    $found = (int) ($disk['percent'] ?? 0);
                    break;
                }
            }
            $values[] = $found;
        }
        $series[] = ['label' => $mount, 'data' => $values];
    }

    return $series;
}

function ms_chart_payload(array $config, string $range = '1d', ?int $fromTs = null, ?int $toTs = null): array
{
    $history = ms_load_history($config);
    $history = ms_filter_history_by_range($history, $range, $fromTs, $toTs);
    $maxChart = max(50, (int) ($config['history_chart_max_points'] ?? 300));
    $history = ms_downsample_history($history, $maxChart);

    $labels = ms_format_history_labels($history, $range);
    $series = [
        'cpu' => [],
        'mem_used_pct' => [],
        'load1' => [],
        'disk_max_pct' => [],
        'network_today_mb' => [],
        'swap_used_mb' => [],
        'connected_users' => [],
    ];

    foreach ($history as $point) {
        foreach (array_keys($series) as $key) {
            $series[$key][] = array_key_exists($key, $point) ? $point[$key] : null;
        }
    }

    return [
        'labels' => $labels,
        'series' => $series,
        'disk_series' => ms_build_disk_history_series($history),
        'points' => count($history),
        'range' => $range,
        'updated_at' => time(),
        'writable' => ms_history_is_writable($config),
        'cron_enabled' => !empty($config['cron_enabled']),
        'needs_cron' => count($history) < 2,
    ];
}

function ms_handle_metrics_api(array $config): bool
{
    if (ms_get('api') !== 'metrics') {
        return false;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $range = (string) ms_get('range', '1d');
    if (!in_array($range, ['1d', '1w', '1m', 'custom'], true)) {
        $range = '1d';
    }

    $fromTs = ms_get('from') !== null ? (int) ms_get('from') : null;
    $toTs = ms_get('to') !== null ? (int) ms_get('to') : null;

    echo json_encode(ms_chart_payload($config, $range, $fromTs, $toTs), JSON_THROW_ON_ERROR);
    return true;
}

function ms_disk_alert_items(array $disks, array $thresholds): array
{
    $items = [];
    $warn = (float) ($thresholds['disk_warning'] ?? 75);
    $crit = (float) ($thresholds['disk_critical'] ?? 90);

    foreach ($disks as $disk) {
        $status = ms_alert_status((float) $disk['percent'], $warn, $crit);
        if ($status === 'ok') {
            continue;
        }

        $items[] = [
            'key' => 'disk_' . $disk['mount'],
            'status' => $status,
            'icon' => 'bi-hdd-stack',
            'message' => sprintf('%s: %d%% used', $disk['mount'], (int) $disk['percent']),
        ];
    }

    return $items;
}
