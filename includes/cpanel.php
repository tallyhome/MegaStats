<?php

declare(strict_types=1);

function ms_cpanel_user(): ?string
{
    foreach (
        [
            $_SERVER['REMOTE_USER'] ?? '',
            $_ENV['REMOTE_USER'] ?? '',
            $_SERVER['REDIRECT_REMOTE_USER'] ?? '',
        ] as $user
    ) {
        if (is_string($user) && $user !== '' && $user !== 'root') {
            return $user;
        }
    }

    return null;
}

function ms_cpanel_account_ip(string $user): ?string
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

function ms_cpanel_primary_domain(string $user): ?string
{
    $file = '/var/cpanel/users/' . $user;
    if (!is_file($file)) {
        return null;
    }
    $content = (string) file_get_contents($file);
    if (preg_match('/^DNS=(.+)$/m', $content, $m)) {
        return trim($m[1]) !== '' ? trim($m[1]) : null;
    }

    return null;
}

function ms_cpanel_require_user(array $config): string
{
    $user = ms_cpanel_user();
    if ($user === null) {
        ms_log($config, 'auth', 'cPanel access denied from ' . ms_client_ip());
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>MegaStats Mail</title></head><body style="font-family:sans-serif;padding:2rem;">';
        echo '<h1>Accès cPanel requis</h1><p>Ouvrez cette page depuis votre session cPanel.</p></body></html>';
        exit;
    }

    return $user;
}

function ms_cpanel_build_mail_view(array $config, string $user): array
{
    $ip = ms_cpanel_account_ip($user);
    $domain = ms_cpanel_primary_domain($user) ?? 'localhost';

    if ($ip === null) {
        return [
            'page_title' => 'MegaStats Mail · ' . $user,
            'user' => $user,
            'ip' => null,
            'error' => 'IP du compte introuvable.',
            'scriptname' => ms_cpanel_request_path(),
            'assets_base' => '/3rdparty/megastats/assets',
            'version' => $config['version'],
        ];
    }

    $forceLive = (string) ms_get('refresh', '') === '1';
    $rbl = ms_mail_get_rbl_for_ip($config, $ip, $forceLive);
    $rbl['grouped'] = ms_mail_group_rbl_by_family($rbl);

    $fcrdns = ms_mail_check_fcrdns($ip, $domain);
    $ptr = ms_mail_check_ptr($ip, (string) ($fcrdns['hostname'] ?? $domain));
    $spf = ms_mail_check_spf($domain);
    $dkim = ms_mail_check_dkim($domain, $config['mail_dkim_selectors'] ?? ['default']);
    $dmarc = ms_mail_check_dmarc($domain);

    $delistZone = trim((string) ms_get('delist', ''));

    return [
        'page_title' => 'Réputation mail · ' . $ip,
        'user' => $user,
        'ip' => $ip,
        'domain' => $domain,
        'rbl' => $rbl,
        'grouped' => $rbl['grouped'],
        'checks' => [
            'ptr' => $ptr,
            'fcrdns' => ms_mail_status((bool) ($fcrdns['ok'] ?? false), $fcrdns['detail'] ?? ''),
            'spf' => $spf,
            'dkim' => $dkim,
            'dmarc' => $dmarc,
        ],
        'listed_count' => (int) ($rbl['listed_count'] ?? 0),
        'delist_guide' => $delistZone !== '' ? ms_mail_delisting_guide($delistZone) : null,
        'delist_zone' => $delistZone !== '' ? $delistZone : null,
        'refresh_url' => ms_url(ms_cpanel_request_path(), ['refresh' => '1']),
        'scriptname' => ms_cpanel_request_path(),
        'assets_base' => '/3rdparty/megastats/assets',
        'version' => $config['version'],
    ];
}

function ms_cpanel_request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : false;
    if (is_string($path) && str_contains($path, 'megastats')) {
        return $path;
    }

    return '/3rdparty/megastats/mail.cgi';
}
