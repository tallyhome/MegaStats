<?php

declare(strict_types=1);

function ms_mail_check_rbl_parallel(string $ip): array
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return ms_mail_check_rbl_sequential($ip);
    }

    $zones = ms_mail_rbl_zones();
    $rev = implode('.', array_reverse(explode('.', $ip)));
    $started = microtime(true);
    $tmp = sys_get_temp_dir() . '/ms-rbl-' . md5($ip . (string) microtime(true));
    @mkdir($tmp, 0700, true);

    $script = "#!/bin/bash\nset +e\n";
    foreach ($zones as $zone => $label) {
        $q = $rev . '.' . $zone;
        $safe = preg_replace('/[^a-z0-9.-]/', '_', $zone);
        $script .= '(dig +short +time=2 +tries=1 ' . escapeshellarg($q) . ' A 2>/dev/null | grep -q . && echo "1" || echo "0") > ' . escapeshellarg($tmp . '/' . $safe) . " &\n";
    }
    $script .= "wait\n";
    $scriptFile = $tmp . '/run.sh';
    file_put_contents($scriptFile, $script);
    @chmod($scriptFile, 0700);
    ms_shell('bash ' . escapeshellarg($scriptFile) . ' 2>/dev/null');

    $listed = [];
    $clean = [];
    foreach ($zones as $zone => $label) {
        $safe = preg_replace('/[^a-z0-9.-]/', '_', $zone);
        $f = $tmp . '/' . $safe;
        $listedOnZone = false;
        $ms = 0;
        if (is_file($f)) {
            $listedOnZone = trim((string) file_get_contents($f)) === '1';
        }
        $entry = [
            'zone' => $zone,
            'label' => $label,
            'listed' => $listedOnZone,
            'response_ms' => $ms,
            'reason' => $listedOnZone ? ('Listed on ' . $label) : 'OK',
        ];
        if ($listedOnZone) {
            $listed[] = $entry;
        } else {
            $clean[] = $entry;
        }
    }

    array_map('unlink', glob($tmp . '/*') ?: []);
    @rmdir($tmp);

    $all = array_merge($listed, $clean);

    return [
        'listed' => $listed,
        'clean' => $clean,
        'all' => $all,
        'listed_count' => count($listed),
        'total_zones' => count($all),
        'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        'parallel' => true,
    ];
}

function ms_mail_check_rbl_sequential(string $ip): array
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return [
            'listed' => [],
            'clean' => [],
            'all' => [],
            'listed_count' => 0,
            'total_zones' => 0,
            'duration_ms' => 0,
        ];
    }

    $rev = implode('.', array_reverse(explode('.', $ip)));
    $listed = [];
    $clean = [];
    $started = microtime(true);

    foreach (ms_mail_rbl_zones() as $zone => $label) {
        $query = $rev . '.' . $zone;
        $t0 = microtime(true);
        $listedOnZone = @checkdnsrr($query, 'A') || @checkdnsrr($query, 'AAAA');
        $ms = (int) round((microtime(true) - $t0) * 1000);
        $entry = [
            'zone' => $zone,
            'label' => $label,
            'listed' => $listedOnZone,
            'response_ms' => $ms,
            'reason' => $listedOnZone ? ('Listed on ' . $label) : 'OK',
        ];
        if ($listedOnZone) {
            $listed[] = $entry;
        } else {
            $clean[] = $entry;
        }
    }

    $all = array_merge($listed, $clean);

    return [
        'listed' => $listed,
        'clean' => $clean,
        'all' => $all,
        'listed_count' => count($listed),
        'total_zones' => count($all),
        'duration_ms' => (int) round((microtime(true) - $started) * 1000),
    ];
}

function ms_mail_check_rbl(string $ip): array
{
    if (is_executable('/usr/bin/dig') || is_executable('/bin/dig')) {
        return ms_mail_check_rbl_parallel($ip);
    }

    return ms_mail_check_rbl_sequential($ip);
}
