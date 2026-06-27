<?php

declare(strict_types=1);

function ms_netstat(string $netstatCom, string $scriptname, ?string $rawOut = null): string
{
    $out = $rawOut ?? ms_shell($netstatCom);
    $out = str_replace(' Address', '_Address', $out);
    $lines = explode("\n", $out);
    $all = '';

    for ($i = 0, $count = count($lines); $i < $count; $i++) {
        $ipStr = '';

        if ($i > 0) {
            $line = preg_replace('/ {1,99}/', '|', $lines[$i]) ?? $lines[$i];
            $line = str_replace('::ffff:', '', $line);
            $parts = explode('|', $line);

            $col0 = str_pad($parts[0] ?? '', 5, ' ', STR_PAD_RIGHT);
            $col1 = str_pad($parts[1] ?? '', 6, ' ', STR_PAD_LEFT);
            $col2 = str_pad($parts[2] ?? '', 6, ' ', STR_PAD_LEFT);
            $col3 = str_pad($parts[3] ?? '', 23, ' ', STR_PAD_RIGHT);

            if (isset($parts[4]) && stripos($parts[4], ':') !== false) {
                $ipStr = explode(':', $parts[4])[0];
            }

            $col4 = str_pad($parts[4] ?? '', 23, ' ', STR_PAD_RIGHT);

            if ($ipStr !== '') {
                $whoisUrl = ms_url($scriptname, ['whois' => $ipStr]);
                $link = '<a href="' . ms_e($whoisUrl) . '" onclick="' . ms_popup_js($whoisUrl, 'whois', 'width=650,height=350,resizable,scrollbars') . '" title="whois ' . ms_e($ipStr) . '">' . ms_e($ipStr) . '</a>';
                $col4 = str_replace($ipStr, $link, $col4);
            }

            $col5 = $parts[5] ?? '';
            $cols = "{$col0} {$col1} {$col2} {$col3} {$col4} {$col5}";
        } else {
            $cols = $lines[$i];
        }

        $all .= "\n" . $cols;
    }

    return str_replace('_Address', ' Address', $all);
}

function ms_vpsstat(): array
{
    $vpsstat = '';
    $mem1 = '';
    $mem1Units = '';
    $mem1Label = '';
    $mem1Tip = '';
    $mem2 = '';
    $mem2Units = '';
    $mem2Label = '';
    $mem2Tip = '';
    $ded = false;
    $beans = '';

    $rawbeans = ms_shell('/bin/beanc 2> /dev/null');

    if ($rawbeans === '') {
        if (file_exists('/proc/user_beancounters')) {
            $rawbeans = ms_shell('cat /proc/user_beancounters 2> /dev/null');
        } else {
            $ded = true;
        }
    }

    if ($rawbeans !== '') {
        $lines = explode("\n", $rawbeans);

        for ($i = 0, $count = count($lines); $i < $count; $i++) {
            if (!preg_match('/oomg|privv|numpr|numt|numo|numfi/', $lines[$i])) {
                continue;
            }

            $line = preg_replace('/ {1,99}/', '|', $lines[$i]) ?? $lines[$i];
            $lineParts = explode('|', $line);

            if (stripos($lines[$i], 'oomg') !== false || stripos($lines[$i], 'privv') !== false) {
                $cur = round(((float) ($lineParts[2] ?? 0)) / 256, 1) . ' MB';
                $rec = round(((float) ($lineParts[3] ?? 0)) / 256, 1) . ' MB';
                $bar = round(((float) ($lineParts[4] ?? 0)) / 256) . ' MB';

                if (stripos($lines[$i], 'oomg') !== false) {
                    $lim = 'n/a';
                    $mem1 = (string) round((float) $cur);
                    $mem1Label = 'oomguarpages';
                    $mem1Tip = 'Guaranteed memory quota';
                    $mem1Units = 'MB';
                } else {
                    $lim = round(((float) ($lineParts[5] ?? 0)) / 256) . ' MB';
                    $mem2 = (string) round((float) $cur);
                    $mem2Label = 'privvmpages';
                    $mem2Tip = 'Burstable memory limit';
                    $mem2Units = 'MB';
                }
            } else {
                $cur = $lineParts[2] ?? '';
                $rec = $lineParts[3] ?? '';
                $bar = 'n/a';
                $lim = $lineParts[5] ?? '';
            }

            $beans .= str_pad($lineParts[1] ?? '', 12)
                . str_pad((string) $cur, 12, ' ', STR_PAD_LEFT)
                . str_pad((string) $rec, 12, ' ', STR_PAD_LEFT)
                . str_pad((string) $bar, 12, ' ', STR_PAD_LEFT)
                . str_pad((string) $lim, 12, ' ', STR_PAD_LEFT)
                . str_pad($lineParts[6] ?? '', 12, ' ', STR_PAD_LEFT)
                . "\n";
        }

        $parts = explode("\n", $beans);
        $vpsstat = "Resource         Current  Recent Max     Barrier       Limit    Failures\n";
        $vpsstat .= "------------  ----------  ----------  ----------  ----------  ----------\n";
        $vpsstat .= ($parts[2] ?? '') . "\n"
            . ($parts[0] ?? '') . "\n"
            . ($parts[1] ?? '') . "\n"
            . ($parts[3] ?? '') . "\n"
            . ($parts[4] ?? '') . "\n"
            . ($parts[5] ?? '');
    }

    if ($vpsstat === '' && $ded === false) {
        $vpsstat = "Virtuozzo/OpenVZ beancounters helper not available.\n";
    } elseif ($ded === true) {
        $free = ms_shell('free');

        if ($free !== '') {
            if (preg_match('/^.*\bMem\b.*$/mi', $free, $hits)) {
                $memline = preg_replace('/ {1,99}/', '|', $hits[0]) ?? $hits[0];
                $parts = explode('|', $memline);
                $mbytes = round(((float) ($parts[3] ?? 0)) / 1024);

                if ($mbytes > 999) {
                    $mem1 = (string) round($mbytes / 1024, 1);
                    $mem1Units = 'GB';
                } else {
                    $mem1 = (string) $mbytes;
                    $mem1Units = 'MB';
                }

                $mem1Label = 'free RAM';
                $mem1Tip = 'Amount of free memory';
            }

            if (preg_match('/^.*\bSwap\b.*$/mi', $free, $hits)) {
                $memline = preg_replace('/ {1,99}/', '|', $hits[0]) ?? $hits[0];
                $parts = explode('|', $memline);
                $mbytes = round(((float) ($parts[2] ?? 0)) / 1024);

                if ($mbytes > 999) {
                    $mem2 = (string) round($mbytes / 1024, 1);
                    $mem2Units = 'GB';
                } else {
                    $mem2 = (string) $mbytes;
                    $mem2Units = 'MB';
                }

                $mem2Label = 'swap used';
                $mem2Tip = 'Swap space currently used';
            }
        }
    }

    return [$vpsstat, $mem1, $mem1Units, $mem1Label, $mem1Tip, $mem2, $mem2Units, $mem2Label, $mem2Tip];
}

function ms_parse_vnstat_today_mb(string $todayColumn): float
{
    $todayColumn = trim($todayColumn);

    if (!preg_match('/([0-9.]+)/', $todayColumn, $matches)) {
        return 0.0;
    }

    $value = (float) $matches[1];

    if (stripos($todayColumn, 'GiB') !== false || stripos($todayColumn, 'GB') !== false) {
        $value *= 1024;
    } elseif (stripos($todayColumn, 'KiB') !== false || stripos($todayColumn, 'KB') !== false) {
        $value /= 1024;
    }

    return $value;
}

function ms_build_load(string $top): array
{
    $loads = [
        ['label' => '1 min', 'value' => 0.0, 'percent' => 0, 'level' => 'success'],
        ['label' => '5 min', 'value' => 0.0, 'percent' => 0, 'level' => 'success'],
        ['label' => '15 min', 'value' => 0.0, 'percent' => 0, 'level' => 'success'],
    ];

    if (!preg_match('/^.*\b(average)\b.*$/mi', $top, $hits)) {
        return $loads;
    }

    $loadParts = explode(',', explode('average:', $hits[0])[1] ?? '0,0,0');
    $values = [
        (float) trim($loadParts[0] ?? '0'),
        (float) trim($loadParts[1] ?? '0'),
        (float) trim($loadParts[2] ?? '0'),
    ];

    foreach ($values as $index => $value) {
        $loads[$index]['value'] = $value;
        $loads[$index]['percent'] = ms_load_percent($value);
        $loads[$index]['level'] = ms_load_level($value);
    }

    return $loads;
}

function ms_build_disks(string $dfFull): array
{
    $disks = [];
    $prev = null;
    $lines = explode("\n", $dfFull);

    for ($i = 0, $count = count($lines); $i < $count; $i++) {
        $line = preg_replace('/ {1,99}/', '|', $lines[$i]) ?? $lines[$i];
        $parts = explode('|', $line);

        if (($parts[0] ?? null) === $prev || stripos($line, 'Filesystem') !== false) {
            $prev = $parts[0] ?? null;
            continue;
        }

        if (!isset($parts[4], $parts[5])) {
            $prev = $parts[0] ?? null;
            continue;
        }

        $percent = (int) substr($parts[4], 0, -1);
        $disks[] = [
            'mount' => $parts[5],
            'size' => $parts[1] ?? '',
            'used' => $parts[2] ?? '',
            'available' => $parts[3] ?? '',
            'percent' => $percent,
            'level' => $percent > 90 ? 'danger' : ($percent > 75 ? 'warning' : 'success'),
        ];

        $prev = $parts[0] ?? null;
    }

    return $disks;
}

function ms_build_services(string $allps, string $processes): array
{
    $services = [];

    foreach (explode(' ', $processes) as $proc) {
        $proc = trim($proc);
        if ($proc === '') {
            continue;
        }

        $services[] = [
            'name' => $proc,
            'up' => stripos($allps, $proc) !== false,
        ];
    }

    return $services;
}

function ms_build_mysql_panel(array $config, string $scriptname): array
{
    $content = '';
    $queries = null;
    $queriesUnit = '';
    $actions = [];
    $mysqlMon = (int) $config['mysql_mon'];

    if ($mysqlMon === 0) {
        return ['content' => '', 'queries' => null, 'queries_unit' => '', 'actions' => []];
    }

    $popup = ms_url($scriptname, ['cmd' => $mysqlMon === 1 ? 'mytop' : 'mysqlreport']);
    $actions[] = [
        'label' => 'Open',
        'url' => $popup,
        'popup' => true,
        'window' => 'mysql',
        'size' => '600,480',
    ];

    if ($mysqlMon === 1) {
        exec('which mytop', $output, $return);
        if ($return === 1) {
            $content = "Mytop is not installed.\n";
        } else {
            $content = ms_shell($config['mysql_com']);
            if (preg_match('/^.*\bQueries\b.*$/mi', $content, $hits)) {
                $parts = explode(' ', trim($hits[0]));
                $queries = is_numeric($parts[1] ?? null) ? round((float) $parts[1]) : null;
            }
        }
        $title = 'mytop';
    } else {
        $mysqlreportPath = MEGASTATS_ROOT . '/mysqlreport';
        $title = 'mysqlreport';

        if (file_exists($mysqlreportPath) && is_executable($mysqlreportPath)) {
            $content = ms_shell($config['mysql_com']);
            if (stripos($content, 'uptime') !== false) {
                $parts = explode("_\n", $content);
                $parts = explode("\n", $parts[2] ?? '');
                $qline = preg_replace('/ {1,99}/', '|', $parts[0] ?? '') ?? '';
                $myParts = explode('|', $qline);
                if (isset($myParts[1])) {
                    if (is_numeric($myParts[1])) {
                        $queries = round((float) $myParts[1]);
                    } else {
                        $unit = strtoupper(substr((string) $myParts[1], -1));
                        if ($unit === 'M') {
                            $queries = round((float) substr((string) $myParts[1], 0, -1), 2);
                            $queriesUnit = 'M';
                        } elseif ($unit === 'K') {
                            $queries = round((float) substr((string) $myParts[1], 0, -1));
                            $queriesUnit = 'K';
                        }
                    }
                }
                $content = str_replace('_', '-', $content);
                $actions[] = [
                    'label' => 'Full Report',
                    'url' => $popup,
                    'popup' => true,
                    'window' => 'mysqlreport',
                    'size' => '600,480',
                ];
            } elseif (stripos($content, 'Access denied') !== false) {
                $content = "mysqlreport: access denied. Check credentials in config/monitoring.php.\n";
            } else {
                $content = "mysqlreport: unknown error.\n";
            }
        } elseif (file_exists($mysqlreportPath)) {
            $content = "mysqlreport exists but is not executable.\n";
        } else {
            $content = "mysqlreport not found in the megastats directory.\n";
        }
    }

    return [
        'title' => $title ?? 'mysql',
        'content' => $content,
        'queries' => $queries,
        'queries_unit' => $queriesUnit,
        'actions' => $actions,
    ];
}

function ms_build_vnstat_panel(array $config, string $scriptname): array
{
    if (!(int) $config['vnstat']) {
        return ['content' => '', 'transfer' => null, 'transfer_unit' => 'MB', 'warn' => false, 'summary' => [], 'actions' => []];
    }

    exec('which vnstat', $output, $return);
    if ($return === 1) {
        return [
            'title' => 'vnstat',
            'content' => "vnstat is not installed.\n",
            'transfer' => null,
            'transfer_unit' => 'MB',
            'warn' => false,
            'summary' => [],
            'actions' => [],
        ];
    }

    $content = ms_shell($config['vnstat_com']);
    $summary = ms_parse_vnstat_summary($content);
    $transfer = null;
    $transferUnit = 'MB';
    $warn = false;

    if ($summary['today_mb'] !== null) {
        $todayMb = (int) $summary['today_mb'];

        if ($todayMb > 999) {
            $transfer = round($todayMb / 1024, 1);
            $transferUnit = 'GB';
        } else {
            $transfer = $todayMb;
        }

        $thresholds = $config['alerts'] ?? [];
        $warn = ms_alert_status(
            (float) $todayMb,
            (float) ($thresholds['network_warning_mb'] ?? 800),
            (float) ($thresholds['network_critical_mb'] ?? 1000)
        ) !== 'ok';
    }

    $actions = [
        ['label' => 'Open', 'url' => ms_url($scriptname, ['cmd' => 'vnstat']), 'popup' => true, 'window' => 'vnstat', 'size' => '525,345'],
        ['label' => 'Sample', 'url' => ms_url($scriptname, ['cmd' => 'vnstat4']), 'popup' => true, 'window' => 'vnstat', 'size' => '525,380'],
        ['label' => 'Days', 'url' => ms_url($scriptname, ['cmd' => 'vnstat2']), 'popup' => true, 'window' => 'vnstat', 'size' => '525,380'],
        ['label' => 'Months', 'url' => ms_url($scriptname, ['cmd' => 'vnstat3']), 'popup' => true, 'window' => 'vnstat', 'size' => '525,380'],
    ];

    return [
        'title' => 'vnstat',
        'content' => $content,
        'transfer' => $transfer,
        'transfer_unit' => $transferUnit,
        'warn' => $warn,
        'summary' => $summary,
        'actions' => $actions,
    ];
}

function ms_build_dashboard(array $config): array
{
    $tstart = microtime(true);
    $scriptname = $config['scriptname'];

    $top = ms_shell_cached($config, 'top', (string) $config['top_com']);
    $hostname = ms_shell_cached($config, 'hostname', 'hostname');
    $uptime = ms_shell_cached($config, 'uptime', 'uptime -p') ?: ms_shell_cached($config, 'uptime_full', 'uptime');
    $netstatRaw = ms_shell_cached($config, 'netstat', (string) $config['netstat_com']);
    $clientStats = ms_count_connected_clients($netstatRaw, $top);
    $connectedUsers = $clientStats['unique_ips'];
    $netstat = ms_netstat($config['netstat_com'], $scriptname, $netstatRaw);
    $pstree = ms_shell_cached($config, 'pstree', (string) $config['pstree_com']);
    $dfFull = ms_shell_cached($config, 'df', (string) $config['df_com']);
    $tmpFull = ms_shell_cached($config, 'tmp', (string) $config['tmp_com']);
    $allps = ms_shell_cached($config, 'allps', (string) $config['allps_com']);

    $netstat = preg_replace("/ {1,99}\n/", "\n", $netstat) ?? $netstat;
    $tmpFull = preg_replace('/ {1,99}/', "\n", $tmpFull) ?? $tmpFull;

    if (!stristr($top, '0 users,')) {
        $usersUrl = ms_url($scriptname, ['users' => '1']);
        $top = preg_replace(
            '/(user|users),/',
            '<a href="' . ms_e($usersUrl) . '" onclick="' . ms_popup_js($usersUrl, 'users', 'width=625,height=300,scrollbars') . '">$1</a>,',
            $top
        ) ?? $top;
    }

    [$vpsstat, $mem1, $mem1Units, $mem1Label, $mem1Tip, $mem2, $mem2Units, $mem2Label, $mem2Tip] = ms_vpsstat();
    $mysqlPanel = ms_build_mysql_panel($config, $scriptname);
    $vnstatPanel = ms_build_vnstat_panel($config, $scriptname);

    $disks = ms_apply_disk_alert_levels(ms_build_disks($dfFull), $config['alerts'] ?? []);
    $loads = ms_apply_load_alert_levels(ms_build_load($top), $config['alerts'] ?? []);
    $memMetrics = ms_parse_memory_metrics(ms_shell_cached($config, 'free', 'free -b'));
    $cpuUsage = ms_parse_cpu_usage($top);
    $networkTodayMb = $vnstatPanel['summary']['today_mb'] ?? null;
    $diskMaxPct = 0;
    foreach ($disks as $disk) {
        $diskMaxPct = max($diskMaxPct, (int) ($disk['percent'] ?? 0));
    }

    $metricsMeta = ['history_points' => count(ms_load_history($config)), 'writable' => ms_history_is_writable($config)];
    if (ms_should_collect_on_dashboard($config)) {
        $metricsMeta = ms_collect_and_record_metrics($config, false);
        $connectedUsers = (int) ($metricsMeta['connected_users'] ?? $connectedUsers);
        if (!empty($metricsMeta['client_stats'])) {
            $clientStats = $metricsMeta['client_stats'];
        }
    }

    $connectionsUrl = ms_url($scriptname, ['connections' => '1']);

    $alertMetrics = [
        'cpu' => $cpuUsage,
        'mem_used_pct' => $memMetrics['mem_used_pct'],
        'load1' => $loads[0]['value'] ?? null,
        'disk_max_pct' => $diskMaxPct,
        'network_today_mb' => $networkTodayMb !== null ? (float) $networkTodayMb : null,
        'disk_alerts' => ms_disk_alert_items($disks, $config['alerts'] ?? []),
    ];
    $activeAlerts = ms_evaluate_alerts($config, $alertMetrics);
    $chartRange = (string) ms_get('range', '1d');
    if (!in_array($chartRange, ['1d', '1w', '1m', 'custom'], true)) {
        $chartRange = '1d';
    }
    $chartFrom = ms_get('from') !== null ? (int) ms_get('from') : null;
    $chartTo = ms_get('to') !== null ? (int) ms_get('to') : null;
    $charts = ms_chart_payload($config, $chartRange, $chartFrom, $chartTo);

    $cleartmpFlash = null;
    $cleartmpFlag = ms_get('cleartmp');
    if (is_string($cleartmpFlag) && $cleartmpFlag !== '') {
        $cleartmpFlash = match ($cleartmpFlag) {
            'ok' => sprintf(
                '/tmp nettoyé : %d fichier(s) supprimé(s), %d ignoré(s).',
                (int) ms_get('deleted', 0),
                (int) ms_get('skipped', 0)
            ),
            'partial' => 'Nettoyage /tmp partiel — certains fichiers n\'ont pas pu être supprimés.',
            'denied' => 'Action refusée : droits insuffisants.',
            'csrf' => 'Jeton de sécurité invalide.',
            'confirm' => 'Confirmation requise pour vider /tmp.',
            default => null,
        };
    }

    $refreshSeconds = 0;
    $refresh = (int) $config['refresh'];
    if ($refresh > 0) {
        $refreshSeconds = max($refresh, 1) * 60;
    }

    $stats = [
        [
            'label' => 'Clients connectés',
            'value' => (string) $connectedUsers,
            'unit' => 'IP',
            'tip' => 'IP uniques distantes (TCP établies) — tous sites et comptes',
            'link' => $connectionsUrl,
            'popup' => true,
        ],
        ['label' => $mem1Label ?: 'Memory', 'value' => $mem1 ?: '—', 'unit' => $mem1Units, 'tip' => $mem1Tip],
        ['label' => $mem2Label ?: 'Swap', 'value' => $mem2 ?: '—', 'unit' => $mem2Units, 'tip' => $mem2Tip],
        ['label' => 'TCP conn', 'value' => (string) ($clientStats['tcp_lines'] ?? ms_count_legacy_tcp_lines($netstatRaw)), 'unit' => '', 'tip' => 'Lignes TCP dans netstat -nt (comme MegaStats 1.x — tous états)'],
        ['label' => 'Apache thds', 'value' => (string) substr_count($pstree, 'httpd'), 'unit' => '', 'tip' => 'Apache processes/threads'],
        ['label' => 'MySQL thds', 'value' => (string) substr_count($pstree, 'mysqld'), 'unit' => '', 'tip' => 'MySQL processes/threads'],
    ];

    if ($cpuUsage !== null) {
        $cpuStatus = ms_alert_status((float) $cpuUsage, (float) ($config['alerts']['cpu_warning'] ?? 70), (float) ($config['alerts']['cpu_critical'] ?? 90));
        $stats[] = [
            'label' => 'CPU usage',
            'value' => (string) $cpuUsage,
            'unit' => '%',
            'tip' => 'CPU utilization',
            'alert' => $cpuStatus,
        ];
    }

    if ($memMetrics['mem_used_pct'] !== null) {
        $ramStatus = ms_alert_status((float) $memMetrics['mem_used_pct'], (float) ($config['alerts']['ram_warning'] ?? 75), (float) ($config['alerts']['ram_critical'] ?? 90));
        $stats[] = [
            'label' => 'RAM used',
            'value' => (string) $memMetrics['mem_used_pct'],
            'unit' => '%',
            'tip' => 'Memory utilization',
            'alert' => $ramStatus,
        ];
    }

    if ($mysqlPanel['queries'] !== null) {
        $stats[] = [
            'label' => 'MySQL queries',
            'value' => (string) $mysqlPanel['queries'],
            'unit' => $mysqlPanel['queries_unit'],
            'tip' => 'Total MySQL queries',
        ];
    }

    if ($vnstatPanel['transfer'] !== null) {
        $netStatus = ms_alert_status(
            (float) ($vnstatPanel['summary']['today_mb'] ?? 0),
            (float) ($config['alerts']['network_warning_mb'] ?? 800),
            (float) ($config['alerts']['network_critical_mb'] ?? 1000)
        );
        $stats[] = [
            'label' => 'Transfer today',
            'value' => (string) $vnstatPanel['transfer'],
            'unit' => $vnstatPanel['transfer_unit'],
            'tip' => 'Data transferred today',
            'alert' => $netStatus,
        ];
    }

    $panels = [
        [
            'id' => 'top',
            'title' => 'top',
            'content' => $top,
            'scroll' => true,
            'actions' => [
                ['label' => 'Open', 'url' => ms_url($scriptname, ['cmd' => 'top']), 'popup' => true, 'window' => 'top', 'size' => '600,480'],
                ['label' => 'ps -aux', 'url' => ms_url($scriptname, ['psaux' => '1']), 'popup' => true, 'window' => 'psaux', 'size' => '730,480'],
                ['label' => 'ps mem', 'url' => ms_url($scriptname, ['psmem' => '1']), 'popup' => true, 'window' => 'psmem', 'size' => '730,480'],
            ],
        ],
        [
            'id' => 'netstat',
            'title' => $config['netstat_com'],
            'content' => $netstat,
            'scroll' => true,
            'actions' => [
                ['label' => 'Open', 'url' => ms_url($scriptname, ['cmd' => 'netstat']), 'popup' => true, 'window' => 'netstat', 'size' => '600,480'],
                ['label' => 'Listening', 'url' => ms_url($scriptname, ['cmd' => 'netstat2']), 'popup' => true, 'window' => 'netstat', 'size' => '600,480'],
                ['label' => 'Port List', 'url' => ms_url($scriptname, ['showports' => '1']), 'popup' => true, 'window' => 'portlist', 'size' => '300,330'],
            ],
        ],
        [
            'id' => 'tmp',
            'title' => 'ls -a /tmp',
            'content' => $tmpFull,
            'note' => 'Ignoring PHP session files (sess_*)',
            'actions' => [
                ['label' => 'ls -al /tmp', 'url' => ms_url($scriptname, ['lsal' => '1']), 'popup' => true, 'window' => 'lsal', 'size' => '730,400'],
            ],
            'clear_tmp' => ms_can_clear_tmp($config),
        ],
        [
            'id' => 'vnstat',
            'title' => $vnstatPanel['title'] ?? 'vnstat',
            'content' => $vnstatPanel['content'],
            'scroll' => true,
            'actions' => $vnstatPanel['actions'],
        ],
        [
            'id' => 'mysql',
            'title' => $mysqlPanel['title'] ?? 'mysql',
            'content' => $mysqlPanel['content'],
            'actions' => $mysqlPanel['actions'],
        ],
        [
            'id' => 'pstree',
            'title' => 'pstree',
            'content' => $pstree,
        ],
    ];

    if ($vpsstat !== '') {
        array_splice($panels, 1, 0, [[
            'id' => 'vpsstat',
            'title' => 'vpsstat',
            'content' => $vpsstat,
            'actions' => [
                ['label' => 'Open', 'url' => ms_url($scriptname, ['cmd' => 'vpsstat']), 'popup' => true, 'window' => 'vpsstat', 'size' => '540,180'],
            ],
        ]]);
    }

    ms_log($config, 'activity', 'Dashboard viewed');

    $updateStatus = ms_update_status($config);
    $mailLatest = !empty($config['mail_enabled']) ? ms_mail_load_latest($config) : null;

    return [
        'hostname' => $hostname,
        'localtime' => $config['localtime'],
        'uptime' => $uptime,
        'version' => $config['version'],
        'pagegen' => 'Generated in ' . round(microtime(true) - $tstart, 4) . ' sec.',
        'scriptname' => $scriptname,
        'assets_base' => $config['assets_base'],
        'refresh_seconds' => $refreshSeconds,
        'services' => ms_build_services($allps, (string) $config['processes']),
        'disks' => $disks,
        'loads' => $loads,
        'stats' => $stats,
        'panels' => $panels,
        'csrf_field' => ms_csrf_field(),
        'user' => $_SESSION['ms_user'] ?? null,
        'active_alerts' => $activeAlerts,
        'vnstat_summary' => $vnstatPanel['summary'] ?? [],
        'charts_json' => json_encode($charts, JSON_THROW_ON_ERROR),
        'metrics_api_url' => ms_api_url($config, ['api' => 'metrics']),
        'chart_range' => $chartRange,
        'chart_from' => $chartFrom,
        'chart_to' => $chartTo,
        'can_clear_tmp' => ms_can_clear_tmp($config),
        'cleartmp_flash' => $cleartmpFlash,
        'donate_url' => (string) ($config['donate_url'] ?? ''),
        'cpanel_compat' => (string) ($config['cpanel_compat'] ?? ''),
        'history_max_points' => (int) ($config['history_max_points'] ?? 43200),
        'history_chart_max_points' => (int) ($config['history_chart_max_points'] ?? 300),
        'chart_refresh_seconds' => (int) ($config['chart_refresh_seconds'] ?? 60),
        'history_points' => (int) ($metricsMeta['history_points'] ?? $charts['points'] ?? 0),
        'history_writable' => (bool) ($metricsMeta['writable'] ?? $charts['writable'] ?? false),
        'history_write_report' => ms_history_writability_report($config),
        'cron_enabled' => !empty($config['cron_enabled']),
        'mail_enabled' => !empty($config['mail_enabled']),
        'mail_url' => ms_url($scriptname, ['page' => 'mail']),
        'mail_score' => $mailLatest['score'] ?? null,
        'update_available' => !empty($updateStatus['update_available']),
        'update_latest' => (string) ($updateStatus['latest'] ?? ''),
        'update_can_run' => ms_update_can_run($config),
        'update_api_url' => ms_api_url($config, ['api' => 'update', 'action' => 'check']),
    ];
}
