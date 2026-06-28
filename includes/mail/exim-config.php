<?php

declare(strict_types=1);

function ms_mail_get_exim_config(): array
{
    $version = '';
    if (is_executable('/usr/sbin/exim') || is_executable('/usr/local/sbin/exim')) {
        $raw = ms_shell('exim -bV 2>/dev/null | head -1');
        if (preg_match('/Exim version ([^\s]+)/i', $raw, $m)) {
            $version = $m[1];
        } elseif ($raw !== '') {
            $version = trim($raw);
        }
    }
    if ($version === '' && is_file('/usr/local/cpanel/version')) {
        $cpVersion = trim((string) file_get_contents('/usr/local/cpanel/version'));
        $version = $cpVersion !== '' ? 'cPanel ' . $cpVersion : '';
    }

    $mainIp = is_file('/var/cpanel/mainip') ? trim((string) file_get_contents('/var/cpanel/mainip')) : '';

    $mailipsRaw = is_file('/etc/mailips') ? (string) file_get_contents('/etc/mailips') : '';
    $mailipsLines = array_values(array_filter(array_map('trim', explode("\n", $mailipsRaw))));
    $mailipsConfigured = $mailipsRaw !== '' && trim($mailipsRaw) !== '';

    $mailheloRaw = is_file('/etc/mailhelo') ? (string) file_get_contents('/etc/mailhelo') : '';
    $mailheloConfigured = $mailheloRaw !== '' && trim($mailheloRaw) !== '';

    $sendFromAccountIp = false;
    if (is_file('/var/cpanel/cpanel.config')) {
        $cfg = (string) file_get_contents('/var/cpanel/cpanel.config');
        $sendFromAccountIp = (bool) preg_match('/^sendmailfromaccountip=1/m', $cfg);
    }

    $usesMainOutgoing = !$mailipsConfigured;
    $issues = [];

    if ($usesMainOutgoing) {
        $issues[] = 'Outgoing IP : utilise l\'IP principale (mailips vide)';
    }
    if (!$mailipsConfigured) {
        $issues[] = 'mailips vide';
    }
    if (!$mailheloConfigured) {
        $issues[] = 'mailhelo non configuré';
    }
    if (!$sendFromAccountIp) {
        $issues[] = 'Send mail from account IP désactivé';
    }

    $ok = $issues === [];

    return [
        'version' => $version,
        'main_ip' => $mainIp,
        'outgoing_ip' => [
            'ok' => !$usesMainOutgoing,
            'label' => $usesMainOutgoing ? 'Utilise l\'IP principale' : 'IP dédiées (mailips)',
            'detail' => $usesMainOutgoing ? ($mainIp !== '' ? $mainIp : '?') : count($mailipsLines) . ' entrée(s)',
        ],
        'mailips' => [
            'ok' => $mailipsConfigured,
            'label' => $mailipsConfigured ? 'Configuré' : 'Vide',
            'lines' => array_slice($mailipsLines, 0, 10),
            'count' => count($mailipsLines),
        ],
        'mailhelo' => [
            'ok' => $mailheloConfigured,
            'label' => $mailheloConfigured ? 'Configuré' : 'Vide',
        ],
        'send_from_account_ip' => [
            'ok' => $sendFromAccountIp,
            'label' => $sendFromAccountIp ? 'Activé' : 'Désactivé',
        ],
        'issues' => $issues,
        'consistent' => $ok,
        'result' => $ok ? 'OK' : 'Incohérence détectée',
        'result_level' => $ok ? 'ok' : 'warn',
    ];
}

/**
 * @return array<string, string> ip => cpuser
 */
function ms_mail_map_ips_to_accounts(): array
{
    $map = [];
    $usersDir = '/var/cpanel/users';
    if (!is_dir($usersDir)) {
        return $map;
    }

    foreach (scandir($usersDir) ?: [] as $user) {
        if ($user === '.' || $user === '..' || str_contains($user, '.')) {
            continue;
        }
        $file = $usersDir . '/' . $user;
        if (!is_file($file)) {
            continue;
        }
        $content = (string) file_get_contents($file);
        if (preg_match('/^IP=(.+)$/m', $content, $m)) {
            $ip = trim($m[1]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $map[$ip] = $user;
            }
        }
    }

    if (is_file('/etc/mailips')) {
        foreach (file('/etc/mailips', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^[^:]+:\s*(\d+\.\d+\.\d+\.\d+)\s*:\s*(\S+)/', $line, $m)) {
                $map[$m[1]] = $m[2];
            }
        }
    }

    return $map;
}
