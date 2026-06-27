<?php

declare(strict_types=1);

function ms_mail_compute_score(array $scan): int
{
    $score = 100;
    $dns = $scan['dns'] ?? [];
    foreach (['spf', 'dkim', 'dmarc', 'ptr'] as $k) {
        if (!($dns[$k]['ok'] ?? false)) {
            $score -= 12;
        }
    }

    $smtp = $scan['smtp'] ?? [];
    foreach (['banner', 'helo', 'tls'] as $k) {
        if (!($smtp[$k]['ok'] ?? false)) {
            $score -= 8;
        }
    }

    $listed = (int) ($scan['rbl_listed'] ?? 0);
    $score -= min(40, $listed * 10);

    $sa = $scan['spamassassin']['score'] ?? null;
    if (is_float($sa) || is_int($sa)) {
        $score -= (int) min(20, max(0, round((float) $sa * 2)));
    }

    foreach ($scan['smtp_tests'] ?? [] as $test) {
        if (!($test['ok'] ?? false)) {
            $score -= 5;
        }
    }

    return max(0, min(100, $score));
}

function ms_mail_run_scan(array $config): array
{
    $domains = ms_mail_detect_domains($config);
    $ips = ms_mail_detect_ips($config);
    $domain = $domains[0] ?? 'localhost';
    $ip = $ips[0] ?? '127.0.0.1';
    $selectors = $config['mail_dkim_selectors'] ?? ['default'];
    $helo = (string) ($config['mail_helo_name'] ?? '');
    if ($helo === '') {
        $helo = $domain;
    }

    $dns = [
        'spf' => ms_mail_check_spf($domain),
        'dkim' => ms_mail_check_dkim($domain, is_array($selectors) ? $selectors : ['default']),
        'dmarc' => ms_mail_check_dmarc($domain),
        'ptr' => ms_mail_check_ptr($ip, $domain),
    ];

    $rbl = ms_mail_check_rbl($ip);
    $rblListed = $rbl['listed'];

    $smtpHost = (string) ($config['mail_smtp_host'] ?? '127.0.0.1');
    $smtpPort = (int) ($config['mail_smtp_port'] ?? 25);
    $smtp = ms_mail_smtp_probe($smtpHost, $smtpPort, $helo);

    $spamassassin = ms_mail_check_spamassassin();

    $mxHosts = $config['mail_test_mx_hosts'] ?? [];
    $smtpTests = [];
    if (is_array($mxHosts)) {
        foreach ($mxHosts as $label => $host) {
            $smtpTests[$label] = ms_mail_test_mx((string) $host);
        }
    }

    $dnsOk = 0;
    foreach ($dns as $d) {
        if ($d['ok'] ?? false) {
            $dnsOk++;
        }
    }

    $scan = [
        'ts' => time(),
        'domain' => $domain,
        'domains' => $domains,
        'ip' => $ip,
        'ips' => $ips,
        'dns' => $dns,
        'dns_ok' => $dnsOk,
        'smtp' => $smtp,
        'rbl' => $rbl,
        'rbl_listed' => count($rblListed),
        'rbl_featured' => array_slice($rbl['all'], 0, 5),
        'spamassassin' => $spamassassin,
        'microsoft' => ms_mail_check_microsoft_snds($config, $ip),
        'google' => ms_mail_check_google_postmaster($domain),
        'yahoo' => ms_mail_check_yahoo($rblListed),
        'smtp_tests' => $smtpTests,
        'expirations' => ms_mail_check_expirations($domain, $dns),
    ];

    $scan['score'] = ms_mail_compute_score($scan);

    ms_mail_save_scan($config, $scan);
    ms_mail_process_rbl_alerts($config, $scan);

    ms_log($config, 'activity', 'Mail deliverability scan score=' . $scan['score']);

    return $scan;
}

function ms_mail_process_rbl_alerts(array $config, array $scan): void
{
    $alertEmail = trim((string) ($config['mail_alert_email'] ?? ''));
    if ($alertEmail === '') {
        $alertEmail = trim((string) ($config['mail_report_email'] ?? ''));
    }
    if ($alertEmail === '') {
        ms_mail_save_rbl_state($config, array_column($scan['rbl']['listed'] ?? [], 'zone'));
        return;
    }

    $previous = ms_mail_previous_rbl_state($config);
    $currentZones = array_column($scan['rbl']['listed'] ?? [], 'zone');
    $newListed = array_diff($currentZones, $previous);

    if ($newListed !== []) {
        $labels = [];
        foreach ($scan['rbl']['listed'] ?? [] as $item) {
            if (in_array($item['zone'], $newListed, true)) {
                $labels[] = $item['label'] . ' (' . $item['zone'] . ')';
            }
        }

        $subject = '[MegaStats] IP ' . ($scan['ip'] ?? '?') . ' listée RBL';
        $body = "Nouvelle(s) liste(s) RBL détectée(s) pour " . ($scan['ip'] ?? '?') . " :\n\n";
        $body .= implode("\n", $labels) . "\n\n";
        $body .= "Consultez MegaStats → Mail & délivrabilité.\n";
        @mail($alertEmail, $subject, $body, 'From: megastats@' . ($scan['domain'] ?? 'localhost'));
        ms_log($config, 'activity', 'RBL alert sent: ' . implode(', ', $labels));
    }

    ms_mail_save_rbl_state($config, $currentZones);
}

function ms_mail_should_run_scheduled(array $config, string $type): bool
{
    $hour = (int) date('G');
    if ($type === 'scan') {
        return $hour === (int) ($config['mail_scan_hour'] ?? 6);
    }
    if ($type === 'report') {
        return $hour === (int) ($config['mail_report_hour'] ?? 7);
    }

    return false;
}

function ms_mail_run_cron(array $config): array
{
    $result = ['scan' => false, 'report' => false];

    if (!($config['mail_enabled'] ?? true)) {
        return $result;
    }

    $force = PHP_SAPI === 'cli' && in_array('--force', $_SERVER['argv'] ?? [], true);

    if ($force || ms_mail_should_run_scheduled($config, 'scan') || !is_file(ms_mail_latest_file($config))) {
        ms_mail_run_scan($config);
        $result['scan'] = true;
    }

    if ($force || ms_mail_should_run_scheduled($config, 'report')) {
        $result['report'] = ms_mail_send_daily_report($config);
    }

    return $result;
}
