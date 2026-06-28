<?php

declare(strict_types=1);

function ms_mail_resolve_a(string $host): array
{
    $host = strtolower(rtrim(trim($host), '.'));
    if ($host === '') {
        return [];
    }

    $ips = [];
    $records = @dns_get_record($host, DNS_A);
    if (is_array($records)) {
        foreach ($records as $rec) {
            if (!empty($rec['ip'])) {
                $ips[] = (string) $rec['ip'];
            }
        }
    }

    if ($ips === []) {
        $ip = @gethostbyname($host);
        if ($ip !== false && $ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
            $ips[] = $ip;
        }
    }

    return array_values(array_unique($ips));
}

function ms_mail_check_fcrdns(string $ip, ?string $expectedHost = null): array
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return ms_mail_status(false, 'IP invalide');
    }

    $ptr = @gethostbyaddr($ip);
    if ($ptr === false || $ptr === '' || $ptr === $ip) {
        return [
            'ok' => false,
            'ptr' => null,
            'hostname' => null,
            'a_records' => [],
            'detail' => 'PTR absent pour ' . $ip,
        ];
    }

    $hostname = strtolower(rtrim($ptr, '.'));
    $aRecords = ms_mail_resolve_a($hostname);
    $fcrdnsOk = in_array($ip, $aRecords, true);

    $detail = $hostname;
    if ($aRecords !== []) {
        $detail .= ' → A: ' . implode(', ', $aRecords);
    } else {
        $detail .= ' → aucun enregistrement A';
    }
    if (!$fcrdnsOk) {
        $detail .= ' (FCrDNS KO — A ne pointe pas vers ' . $ip . ')';
    }

    if ($expectedHost !== null && $expectedHost !== '') {
        $expected = strtolower(rtrim($expectedHost, '.'));
        $ptrOk = $hostname === $expected || str_ends_with($hostname, '.' . $expected);
        if (!$ptrOk) {
            $fcrdnsOk = false;
            $detail .= ' ; PTR attendu ~ ' . $expected;
        }
    }

    return [
        'ok' => $fcrdnsOk,
        'ptr' => $ptr,
        'hostname' => $hostname,
        'a_records' => $aRecords,
        'detail' => $detail,
    ];
}

function ms_mail_check_spf_includes_ip(string $domain, string $ip): array
{
    $spf = ms_mail_check_spf($domain);
    if (!($spf['ok'] ?? false)) {
        return ms_mail_status(false, 'SPF absent — IP non couverte');
    }

    $txt = (string) ($spf['detail'] ?? '');
    if (preg_match('/\bip4:' . preg_quote($ip, '/') . '\b/i', $txt)) {
        return ms_mail_status(true, 'IP présente dans SPF');
    }
    if (preg_match('/\bip4:\d+\.\d+\.\d+\.\d+\/\d+\b/i', $txt)) {
        foreach (preg_split('/\s+/', $txt) as $part) {
            if (preg_match('/^ip4:(\d+\.\d+\.\d+\.\d+)\/(\d+)$/i', $part, $m)) {
                if (ms_mail_ip_in_cidr($ip, $m[1], (int) $m[2])) {
                    return ms_mail_status(true, 'IP dans plage SPF ' . $part);
                }
            }
        }
    }
    if (stripos($txt, '+a') !== false || stripos($txt, '+mx') !== false) {
        return ms_mail_status(null, 'SPF présent mais IP non explicite (+a/+mx) — vérifier manuellement');
    }

    return ms_mail_status(false, 'IP absente du SPF : ' . mb_substr($txt, 0, 120));
}

function ms_mail_ip_in_cidr(string $ip, string $network, int $bits): bool
{
    $ipLong = ip2long($ip);
    $netLong = ip2long($network);
    if ($ipLong === false || $netLong === false) {
        return false;
    }
    $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));

    return ($ipLong & $mask) === ($netLong & $mask);
}

function ms_mail_check_helo_fcrdns(array $smtpHelo, array $fcrdns): array
{
    $heloOk = (bool) ($smtpHelo['ok'] ?? false);
    $fcrdnsOk = (bool) ($fcrdns['ok'] ?? false);
    $hostname = (string) ($fcrdns['hostname'] ?? '');

    if (!$heloOk) {
        return ms_mail_status(false, 'EHLO/HELO SMTP en échec');
    }
    if (!$fcrdnsOk || $hostname === '') {
        return ms_mail_status(false, 'FCrDNS invalide — HELO doit correspondre au hostname PTR');
    }

    $heloDetail = strtolower((string) ($smtpHelo['detail'] ?? ''));
    if ($heloDetail !== '' && !str_contains($heloDetail, $hostname)) {
        return ms_mail_status(false, 'HELO (' . trim($heloDetail) . ') ≠ hostname FCrDNS (' . $hostname . ')');
    }

    return ms_mail_status(true, 'HELO cohérent avec FCrDNS (' . $hostname . ')');
}
