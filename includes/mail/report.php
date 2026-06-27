<?php

declare(strict_types=1);

function ms_mail_icon(?bool $ok): string
{
    if ($ok === true) {
        return '✔';
    }
    if ($ok === false) {
        return '✖';
    }

    return '—';
}

function ms_mail_level_dot(string $level): string
{
    return match ($level) {
        'good', 'excellent' => '🟢',
        'warn', 'warning' => '🟡',
        'bad', 'critical' => '🔴',
        default => '⚪',
    };
}

function ms_mail_format_report_text(array $scan): string
{
    $lines = [];
    $lines[] = 'MegaStats — Rapport délivrabilité mail';
    $lines[] = 'Date : ' . date('Y-m-d H:i:s', (int) ($scan['ts'] ?? time()));
    $lines[] = 'Domaine : ' . ($scan['domain'] ?? '?') . ' | IP : ' . ($scan['ip'] ?? '?');
    $lines[] = 'Score : ' . ($scan['score'] ?? 0) . '/100';
    $lines[] = str_repeat('-', 50);
    $lines[] = 'DNS & SMTP';
    foreach (['spf', 'dkim', 'dmarc', 'ptr'] as $k) {
        $d = $scan['dns'][$k] ?? ['ok' => false];
        $lines[] = strtoupper($k) . ' : ' . ms_mail_icon($d['ok'] ?? false);
    }
    foreach (['banner', 'helo', 'tls'] as $k) {
        $s = $scan['smtp'][$k] ?? ['ok' => false];
        $lines[] = strtoupper($k) . ' : ' . ms_mail_icon($s['ok'] ?? false);
    }
    $lines[] = str_repeat('-', 50);
    $lines[] = 'RBL listées : ' . (int) ($scan['rbl_listed'] ?? 0);
    foreach ($scan['rbl']['listed'] ?? [] as $item) {
        $lines[] = '  ✖ ' . $item['label'];
    }
    $lines[] = str_repeat('-', 50);
    $sa = $scan['spamassassin']['score'] ?? null;
    $lines[] = 'SpamAssassin : ' . ($sa !== null ? $sa . ' / 10' : 'N/A');
    $lines[] = str_repeat('-', 50);
    foreach ($scan['smtp_tests'] ?? [] as $name => $test) {
        $lines[] = ucfirst((string) $name) . ' MX : ' . ms_mail_icon($test['ok'] ?? false);
    }

    return implode("\n", $lines) . "\n";
}

function ms_mail_send_daily_report(array $config): bool
{
    $to = trim((string) ($config['mail_report_email'] ?? ''));
    if ($to === '') {
        return false;
    }

    $scan = ms_mail_load_latest($config);
    if ($scan === null) {
        $scan = ms_mail_run_scan($config);
    }

    $domain = $scan['domain'] ?? 'localhost';
    $subject = '[MegaStats] Rapport mail ' . date('Y-m-d') . ' — score ' . ($scan['score'] ?? 0) . '/100';
    $body = ms_mail_format_report_text($scan);
    $headers = 'From: megastats@' . $domain . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';

    $sent = @mail($to, $subject, $body, $headers);
    if ($sent) {
        ms_log($config, 'activity', 'Mail daily report sent to ' . $to);
    }

    return $sent;
}

function ms_mail_build_page_view(array $config): array
{
    $scan = ms_mail_load_latest($config);
    $history = ms_mail_load_history($config);
    $scriptname = $config['scriptname'];

    return [
        'page_title' => 'Mail & délivrabilité · MegaStats',
        'scan' => $scan,
        'history' => $history,
        'history_json' => json_encode($history, JSON_THROW_ON_ERROR),
        'scriptname' => $scriptname,
        'dashboard_url' => ms_url($scriptname),
        'mail_url' => ms_url($scriptname, ['page' => 'mail']),
        'assets_base' => $config['assets_base'],
        'version' => $config['version'],
        'pagegen' => '',
        'csrf_field' => ms_csrf_field(),
        'can_scan' => true,
        'storage_ok' => ms_mail_ensure_storage($config),
        'mail_path' => ms_mail_path($config),
        'whm_embedded' => !empty($config['deployment']) && $config['deployment'] === 'whm',
    ];
}
