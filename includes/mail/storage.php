<?php

declare(strict_types=1);

function ms_mail_path(array $config): string
{
    $path = (string) ($config['mail_path'] ?? '');
    if ($path !== '') {
        return rtrim($path, '/');
    }

    if (defined('MEGASTATS_WHM') && MEGASTATS_WHM) {
        return '/var/cpanel/megastats/mail';
    }

    return MEGASTATS_ROOT . '/storage/mail';
}

function ms_mail_ensure_storage(array $config): bool
{
    $base = ms_mail_path($config);
    foreach ([$base, $base . '/history'] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
    }

    return is_writable($base);
}

function ms_mail_latest_file(array $config): string
{
    return ms_mail_path($config) . '/latest.json';
}

function ms_mail_history_file(array $config): string
{
    return ms_mail_path($config) . '/history/daily.json';
}

function ms_mail_load_latest(array $config): ?array
{
    $file = ms_mail_latest_file($config);
    if (!is_file($file)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function ms_mail_save_scan(array $config, array $scan): bool
{
    if (!ms_mail_ensure_storage($config)) {
        return false;
    }

    $file = ms_mail_latest_file($config);
    $json = json_encode($scan, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        return false;
    }

    ms_mail_append_history($config, $scan);
    return true;
}

function ms_mail_append_history(array $config, array $scan): void
{
    $file = ms_mail_history_file($config);
    $history = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $history = $decoded;
        }
    }

    $day = date('Y-m-d', (int) ($scan['ts'] ?? time()));
    $entry = [
        'date' => $day,
        'score' => (int) ($scan['score'] ?? 0),
        'rbl_listed' => (int) ($scan['rbl_listed'] ?? 0),
        'spam_score' => (float) ($scan['spamassassin']['score'] ?? 0),
        'dns_ok' => (int) ($scan['dns_ok'] ?? 0),
    ];

    $history[$day] = $entry;
    $maxDays = (int) ($config['mail_history_days'] ?? 90);
    if (count($history) > $maxDays) {
        ksort($history);
        $history = array_slice($history, -$maxDays, null, true);
    }

    file_put_contents($file, json_encode($history, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), LOCK_EX);
}

function ms_mail_load_history(array $config): array
{
    $file = ms_mail_history_file($config);
    if (!is_file($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        return [];
    }

    ksort($decoded);
    return array_values($decoded);
}

function ms_mail_previous_rbl_state(array $config): array
{
    $file = ms_mail_path($config) . '/rbl_state.json';
    if (!is_file($file)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function ms_mail_save_rbl_state(array $config, array $listed): void
{
    if (!ms_mail_ensure_storage($config)) {
        return;
    }

    $file = ms_mail_path($config) . '/rbl_state.json';
    file_put_contents($file, json_encode($listed, JSON_THROW_ON_ERROR), LOCK_EX);
}
