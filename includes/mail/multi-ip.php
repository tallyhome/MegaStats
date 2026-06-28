<?php

declare(strict_types=1);

function ms_mail_analyze_ip_row(string $ip, array $config, string $domain, array $accountMap): array
{
    $ptrHost = null;
    $fcrdns = ms_mail_check_fcrdns($ip);
    $ptr = ms_mail_check_ptr($ip, $domain);
    if (!empty($fcrdns['hostname'])) {
        $ptrHost = (string) $fcrdns['hostname'];
        $ptr = ms_mail_check_ptr($ip, $ptrHost);
    }

    $aOk = !empty($fcrdns['a_records']) && in_array($ip, $fcrdns['a_records'], true);
    $spfIp = ms_mail_check_spf_includes_ip($domain, $ip);
    $dkim = ms_mail_check_dkim($domain, $config['mail_dkim_selectors'] ?? ['default']);
    $dmarc = ms_mail_check_dmarc($domain);

    $helo = (string) ($config['mail_helo_name'] ?? '');
    if ($helo === '' && $ptrHost !== null) {
        $helo = $ptrHost;
    }
    if ($helo === '') {
        $helo = $domain;
    }
    $smtpHost = (string) ($config['mail_smtp_host'] ?? '127.0.0.1');
    $smtpPort = (int) ($config['mail_smtp_port'] ?? 25);
    $smtpProbe = ms_mail_smtp_probe($smtpHost, $smtpPort, $helo);
    $heloCoherence = ms_mail_check_helo_fcrdns($smtpProbe['helo'] ?? ms_mail_status(false), $fcrdns);

    $rbl = ms_mail_check_rbl($ip);
    $rblGrouped = ms_mail_group_rbl_by_family($rbl);

    $account = $accountMap[$ip] ?? null;
    $mailIpOk = $account !== null
        && ms_mail_send_from_account_ip_enabled()
        && ms_mail_account_uses_dedicated_ip($account, $ip);
    $microsoft = ms_mail_check_microsoft_for_ip($config, $ip, $rblGrouped);
    $score = ms_mail_compute_ip_score($ptr, $fcrdns, $spfIp, $dkim, $dmarc, $heloCoherence, $rblGrouped);

    return [
        'ip' => $ip,
        'account' => $account,
        'mail_ip' => ms_mail_status($mailIpOk, $mailIpOk ? 'Account IP activé' : 'Vérifier sendmailfromaccountip / IP compte'),
        'microsoft' => $microsoft,
        'ptr' => $ptr,
        'a' => ms_mail_status($aOk, $aOk ? implode(', ', $fcrdns['a_records']) : 'A manquant ou incorrect'),
        'fcrdns' => ms_mail_status((bool) ($fcrdns['ok'] ?? false), $fcrdns['detail'] ?? ''),
        'spf' => $spfIp,
        'dkim' => $dkim,
        'dmarc' => $dmarc,
        'helo' => $heloCoherence,
        'rbl' => $rbl,
        'rbl_grouped' => $rblGrouped,
        'rbl_listed' => (int) ($rbl['listed_count'] ?? 0),
        'score' => $score,
        'grade' => ms_mail_grade_from_score($score),
    ];
}

function ms_mail_compute_ip_score(array $ptr, array $fcrdns, array $spf, array $dkim, array $dmarc, array $helo, array $rblGrouped): int
{
    $score = 100;
    foreach ([$ptr, $fcrdns, $spf, $dkim, $dmarc, $helo] as $check) {
        if (($check['ok'] ?? null) === false) {
            $score -= 12;
        } elseif (($check['ok'] ?? null) === null) {
            $score -= 6;
        }
    }
    $listed = (int) ($rblGrouped['listed_count'] ?? 0);
    $critical = (int) ($rblGrouped['critical_families'] ?? 0);
    $score -= min(35, $listed * 4);
    $score -= min(20, $critical * 10);

    return max(0, min(100, $score));
}

function ms_mail_build_ip_matrix(array $config): array
{
    $ips = ms_mail_detect_all_ips($config);
    $domain = ms_mail_detect_domains($config)[0] ?? 'localhost';
    $accountMap = ms_mail_map_ips_to_accounts();
    $rows = [];

    foreach ($ips as $ip) {
        $rows[] = ms_mail_analyze_ip_row($ip, $config, $domain, $accountMap);
    }

    return [
        'domain' => $domain,
        'rows' => $rows,
        'count' => count($rows),
    ];
}
