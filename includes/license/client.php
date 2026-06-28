<?php

declare(strict_types=1);

function ms_license_install_id(array $config): string
{
    $path = ms_mail_path($config) . '/license/install_id';
    if (is_file($path)) {
        $id = trim((string) file_get_contents($path));
        if ($id !== '') {
            return $id;
        }
    }

    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0x0fff) | 0x4000, random_int(0, 0x3fff) | 0x8000, random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff));
    @mkdir(dirname($path), 0750, true);
    file_put_contents($path, $id);

    return $id;
}

function ms_license_heartbeat(array $config): array
{
    $serial = trim((string) ($config['license_serial'] ?? ''));
    if ($serial === '' || !($config['license_heartbeat_enabled'] ?? false)) {
        return ['ok' => true, 'skipped' => true];
    }

    $url = rtrim((string) ($config['license_api_url'] ?? ''), '/') . '/megastats/heartbeat';
    $payload = json_encode([
        'serial' => $serial,
        'install_id' => ms_license_install_id($config),
        'version' => $config['version'] ?? '',
        'product' => 'megastats',
    ], JSON_THROW_ON_ERROR);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);

    return [
        'ok' => $response !== false,
        'response' => is_string($response) ? $response : '',
    ];
}

function ms_license_maybe_heartbeat(array $config): void
{
    $serial = trim((string) ($config['license_serial'] ?? ''));
    if ($serial === '') {
        return;
    }

    $stateFile = ms_mail_path($config) . '/license/last_heartbeat';
    $interval = (int) ($config['license_heartbeat_interval'] ?? 86400);
    if (is_file($stateFile) && (time() - (int) filemtime($stateFile)) < $interval) {
        return;
    }

    ms_license_heartbeat($config);
    @touch($stateFile);
}
