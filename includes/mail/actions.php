<?php

declare(strict_types=1);

function ms_mail_handle_post_action(array $config): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }

    $action = (string) ms_post('mail_action', '');
    if ($action === '') {
        return null;
    }

    if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
        return 'csrf';
    }

    match ($action) {
        'scan', 'scan_all' => ms_mail_run_scan($config),
        'scan_ip' => ms_mail_refresh_ip($config, trim((string) ms_post('scan_ip', ''))),
        default => null,
    };

    return match ($action) {
        'scan' => 'ok',
        'scan_all' => 'ok_all',
        'scan_ip' => 'ok_ip',
        default => null,
    };
}

function ms_mail_scan_flash_message(string $code): string
{
    return match ($code) {
        'ok' => 'Analyse terminée.',
        'ok_all' => 'Analyse de toutes les IP terminée.',
        'ok_ip' => 'Analyse de l\'IP terminée.',
        'csrf' => 'Jeton de sécurité invalide.',
        default => '',
    };
}
