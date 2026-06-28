<?php

declare(strict_types=1);

function ms_mail_exim_backup_dir(): string
{
    $dir = '/var/cpanel/megastats/mail/backups';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    return $dir;
}

function ms_mail_exim_backup_path(string $basename): string
{
    return ms_mail_exim_backup_dir() . '/' . $basename . '-' . date('Y-m-d-His') . '.bak';
}

/** @return array<string, string> cpuser => ipv4 */
function ms_mail_parse_userips(): array
{
    $map = [];
    if (!is_file('/etc/userips')) {
        return $map;
    }

    foreach (file('/etc/userips', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (preg_match('/^([^:\s]+)\s*:\s*(\d+\.\d+\.\d+\.\d+)\s*$/', $line, $m)) {
            $map[trim($m[1])] = trim($m[2]);
            continue;
        }
        if (preg_match('/^(\S+)\s+(\d+\.\d+\.\d+\.\d+)\s*$/', $line, $m)) {
            $map[trim($m[1])] = trim($m[2]);
        }
    }

    return $map;
}

function ms_mail_user_primary_domain(string $user): ?string
{
    $file = '/var/cpanel/users/' . $user;
    if (!is_file($file)) {
        return null;
    }
    $content = (string) file_get_contents($file);
    if (preg_match('/^DNS=(.+)$/m', $content, $m)) {
        $domain = strtolower(rtrim(trim($m[1]), '.'));
        return $domain !== '' ? $domain : null;
    }

    return null;
}

function ms_mail_user_ip(string $user): ?string
{
    $userIps = ms_mail_parse_userips();
    if (isset($userIps[$user]) && filter_var($userIps[$user], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $userIps[$user];
    }

    $file = '/var/cpanel/users/' . $user;
    if (!is_file($file)) {
        return null;
    }
    $content = (string) file_get_contents($file);
    if (preg_match('/^IP=(.+)$/m', $content, $m)) {
        $ip = trim($m[1]);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
    }

    return null;
}

/**
 * Lit /etc/userdomains, /etc/userips et /var/cpanel/users/* pour construire mailips.
 *
 * @return list<array{domain:string,ip:string,user:string}>
 */
function ms_mail_collect_mailips_entries(): array
{
    $entries = [];
    $seen = [];
    $userIps = ms_mail_parse_userips();

    $add = static function (string $domain, string $ip, string $user) use (&$entries, &$seen): void {
        $domain = strtolower(rtrim(trim($domain), '.'));
        $user = trim($user);
        $ip = trim($ip);
        if ($domain === '' || $user === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return;
        }
        $key = $domain . '|' . $ip . '|' . $user;
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $entries[] = ['domain' => $domain, 'ip' => $ip, 'user' => $user];
    };

    if (is_file('/etc/userdomains')) {
        foreach (file('/etc/userdomains', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = preg_split('/\s*:\s*/', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $domain = trim($parts[0]);
            $user = trim($parts[1]);
            $ip = $userIps[$user] ?? ms_mail_user_ip($user);
            if ($ip !== null) {
                $add($domain, $ip, $user);
            }
        }
    }

    $usersDir = '/var/cpanel/users';
    if (is_dir($usersDir)) {
        foreach (scandir($usersDir) ?: [] as $user) {
            if ($user === '.' || $user === '..' || str_contains($user, '.')) {
                continue;
            }
            if (!is_file($usersDir . '/' . $user)) {
                continue;
            }
            $ip = $userIps[$user] ?? ms_mail_user_ip($user);
            $domain = ms_mail_user_primary_domain($user);
            if ($ip !== null && $domain !== null) {
                $add($domain, $ip, $user);
            }
        }
    }

    foreach ($userIps as $user => $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            continue;
        }
        $domain = ms_mail_user_primary_domain($user);
        if ($domain !== null) {
            $add($domain, $ip, $user);
        }
    }

    usort($entries, static fn(array $a, array $b): int => strcmp($a['domain'], $b['domain']));

    return $entries;
}

function ms_mail_server_hostname(): string
{
    if (is_file('/etc/myhostname')) {
        $hn = trim((string) file_get_contents('/etc/myhostname'));
        if ($hn !== '') {
            return strtolower(rtrim($hn, '.'));
        }
    }
    if (is_file('/etc/hostname')) {
        $hn = trim((string) file_get_contents('/etc/hostname'));
        if ($hn !== '') {
            return strtolower(rtrim($hn, '.'));
        }
    }
    $hn = gethostname();

    return is_string($hn) && $hn !== '' ? strtolower(rtrim($hn, '.')) : 'localhost';
}

function ms_mail_helo_hostname_for_ip(string $ip, string $domain, array $config = []): string
{
    $fcrdns = ms_mail_check_fcrdns($ip);
    if (!empty($fcrdns['hostname'])) {
        return strtolower(rtrim((string) $fcrdns['hostname'], '.'));
    }

    $prefix = (string) ($config['mail_hostname_prefix'] ?? 'mail-r');
    $hostDomain = trim((string) ($config['mail_hostname_domain'] ?? ''));
    if ($hostDomain === '') {
        $hostDomain = $domain;
    }
    $suffix = preg_replace('/\D/', '', strrchr($ip, '.') ?: '1') ?: '1';

    return strtolower($prefix . $suffix . '.' . rtrim($hostDomain, '.'));
}

/**
 * @param list<array{domain:string,ip:string,user:string}> $mailipsEntries
 * @return list<array{domain:string,helo:string}>
 */
function ms_mail_collect_mailhelo_entries(array $mailipsEntries, array $config = []): array
{
    $byDomain = [];
    $ipHelos = [];

    foreach ($mailipsEntries as $entry) {
        $domain = $entry['domain'];
        $ip = $entry['ip'];
        if (!isset($ipHelos[$ip])) {
            $ipHelos[$ip] = ms_mail_helo_hostname_for_ip($ip, $domain, $config);
        }
        $byDomain[$domain] = $ipHelos[$ip];
    }

    $entries = [];
    foreach ($byDomain as $domain => $helo) {
        $entries[] = ['domain' => $domain, 'helo' => $helo];
    }

    usort($entries, static fn(array $a, array $b): int => strcmp($a['domain'], $b['domain']));

    return $entries;
}

function ms_mail_format_mailips_lines(array $entries): string
{
    $lines = [];
    foreach ($entries as $entry) {
        $lines[] = $entry['domain'] . ': ' . $entry['ip'] . ' : ' . $entry['user'];
    }

    return implode("\n", $lines) . ($lines !== [] ? "\n" : '');
}

/**
 * @param list<array{domain:string,helo:string}> $entries
 */
function ms_mail_format_mailhelo_lines(array $entries, ?string $defaultHelo = null): string
{
    $lines = [];
    foreach ($entries as $entry) {
        $lines[] = $entry['domain'] . ': ' . $entry['helo'];
    }
    $defaultHelo ??= ms_mail_server_hostname();
    if ($defaultHelo !== '') {
        $lines[] = '*: ' . $defaultHelo;
    }

    return implode("\n", $lines) . ($lines !== [] ? "\n" : '');
}

/** @return array{mailips: list<array{domain:string,ip:string,user:string}>, mailhelo: list<array{domain:string,helo:string}>, default_helo: string} */
function ms_mail_preview_exim_rebuild(array $config = []): array
{
    $mailips = ms_mail_collect_mailips_entries();
    $mailhelo = ms_mail_collect_mailhelo_entries($mailips, $config);

    return [
        'mailips' => $mailips,
        'mailhelo' => $mailhelo,
        'default_helo' => ms_mail_server_hostname(),
    ];
}

function ms_mail_write_exim_file(string $path, string $content, string $backupBasename): bool
{
    if (is_file($path)) {
        @copy($path, ms_mail_exim_backup_path($backupBasename));
    }

    if (@file_put_contents($path, $content) === false) {
        return false;
    }

    @chmod($path, 0644);

    return true;
}

/** @return array{ok:bool, command:string, output:string} */
function ms_mail_reload_exim_service(): array
{
    $commands = [
        'systemctl reload exim',
        'systemctl reload exim4',
        '/scripts/reloadsrv_exim',
        '/scripts/restartsrv_exim --wait',
    ];

    foreach ($commands as $command) {
        $bin = strtok($command, ' ');
        if ($bin === false) {
            continue;
        }
        if (str_starts_with($bin, '/')) {
            if (!is_executable($bin)) {
                continue;
            }
        } elseif (trim((string) ms_shell('command -v ' . escapeshellarg($bin) . ' 2>/dev/null')) === '') {
            continue;
        }

        $output = trim(ms_shell($command . ' 2>&1'));
        $lower = strtolower($output);
        $hardFail = $output !== ''
            && (str_contains($lower, 'failed')
                || str_contains($lower, 'not found')
                || str_contains($lower, 'could not')
                || str_contains($lower, 'invalid'));

        if (!$hardFail) {
            return ['ok' => true, 'command' => $command, 'output' => $output];
        }
    }

    return ['ok' => false, 'command' => '', 'output' => 'Impossible de recharger Exim (systemctl/scripts).'];
}

/**
 * Régénère /etc/mailips et /etc/mailhelo depuis cPanel puis recharge Exim.
 */
function ms_mail_rebuild_exim_config(array $config = []): array
{
    if ((function_exists('posix_geteuid') ? posix_geteuid() : 0) !== 0) {
        return ['ok' => false, 'message' => 'Root requis pour régénérer mailips/mailhelo.'];
    }

    $mailipsEntries = ms_mail_collect_mailips_entries();
    if ($mailipsEntries === []) {
        return [
            'ok' => false,
            'message' => 'Aucune entrée trouvée (/etc/userdomains, /etc/userips, /var/cpanel/users).',
        ];
    }

    $mailheloEntries = ms_mail_collect_mailhelo_entries($mailipsEntries, $config);
    $mailipsContent = ms_mail_format_mailips_lines($mailipsEntries);
    $mailheloContent = ms_mail_format_mailhelo_lines($mailheloEntries);

    if (!ms_mail_write_exim_file('/etc/mailips', $mailipsContent, 'mailips')) {
        return ['ok' => false, 'message' => 'Impossible d\'écrire /etc/mailips'];
    }
    if (!ms_mail_write_exim_file('/etc/mailhelo', $mailheloContent, 'mailhelo')) {
        return ['ok' => false, 'message' => 'Impossible d\'écrire /etc/mailhelo'];
    }

    $reload = ms_mail_reload_exim_service();

    return [
        'ok' => $reload['ok'],
        'message' => count($mailipsEntries) . ' entrée(s) mailips + '
            . count($mailheloEntries) . ' HELO écrits.'
            . ($reload['ok'] ? ' Exim rechargé (' . $reload['command'] . ').' : ' — Échec reload Exim.'),
        'mailips' => $mailipsEntries,
        'mailhelo' => $mailheloEntries,
        'mailips_count' => count($mailipsEntries),
        'mailhelo_count' => count($mailheloEntries),
        'reload' => $reload,
        'sources' => [
            'userdomains' => is_file('/etc/userdomains'),
            'userips' => is_file('/etc/userips'),
            'cpanel_users' => is_dir('/var/cpanel/users'),
        ],
    ];
}

/** @deprecated alias v3.5 — utilise ms_mail_rebuild_exim_config */
function ms_mail_rebuild_mailips(): array
{
    return ms_mail_rebuild_exim_config([]);
}
