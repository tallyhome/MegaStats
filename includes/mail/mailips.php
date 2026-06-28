<?php

declare(strict_types=1);

function ms_mail_mailips_backup_path(): string
{
    $dir = '/var/cpanel/megastats/mail/backups';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    return $dir . '/mailips-' . date('Y-m-d-His') . '.bak';
}

/**
 * @return list<array{domain:string,ip:string,user:string}>
 */
function ms_mail_collect_mailips_entries(): array
{
    $entries = [];
    $seen = [];

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
            $ip = ms_mail_user_ip($user);
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
            $content = (string) file_get_contents($usersDir . '/' . $user);
            $ip = null;
            $domain = null;
            if (preg_match('/^IP=(.+)$/m', $content, $m)) {
                $ip = trim($m[1]);
            }
            if (preg_match('/^DNS=(.+)$/m', $content, $m)) {
                $domain = trim($m[1]);
            }
            if ($ip !== null && $domain !== null && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $add($domain, $ip, $user);
            }
        }
    }

    usort($entries, static fn(array $a, array $b): int => strcmp($a['domain'], $b['domain']));

    return $entries;
}

function ms_mail_user_ip(string $user): ?string
{
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

function ms_mail_format_mailips_lines(array $entries): string
{
    $lines = [];
    foreach ($entries as $entry) {
        $lines[] = $entry['domain'] . ': ' . $entry['ip'] . ' : ' . $entry['user'];
    }

    return implode("\n", $lines) . ( $lines !== [] ? "\n" : '');
}

function ms_mail_rebuild_mailips(): array
{
    if ((function_exists('posix_geteuid') ? posix_geteuid() : 0) !== 0) {
        return ['ok' => false, 'message' => 'Root requis pour reconstruire /etc/mailips.'];
    }

    $entries = ms_mail_collect_mailips_entries();
    if ($entries === []) {
        return ['ok' => false, 'message' => 'Aucun compte/domaine trouvé (/etc/userdomains ou /var/cpanel/users).'];
    }

    $content = ms_mail_format_mailips_lines($entries);
    $target = '/etc/mailips';

    if (is_file($target)) {
        @copy($target, ms_mail_mailips_backup_path());
    }

    if (@file_put_contents($target, $content) === false) {
        return ['ok' => false, 'message' => 'Impossible d\'écrire ' . $target];
    }

    @chmod($target, 0644);

    $restart = ms_shell('/scripts/restartsrv_exim --wait 2>&1');

    return [
        'ok' => true,
        'message' => count($entries) . ' entrée(s) écrites dans /etc/mailips. Exim redémarré.',
        'entries' => $entries,
        'count' => count($entries),
        'restart_output' => trim($restart),
    ];
}
