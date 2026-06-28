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

    $mailipsResult = null;
    $fixResult = null;
    match ($action) {
        'scan', 'scan_all' => ms_mail_run_scan($config),
        'scan_ip' => ms_mail_refresh_ip($config, trim((string) ms_post('scan_ip', ''))),
        'rebuild_mailips' => (function () use ($config, &$mailipsResult): void {
            $mailipsResult = ms_mail_rebuild_exim_config($config);
            if ($mailipsResult['ok']) {
                ms_mail_run_scan($config);
                @file_put_contents(
                    ms_mail_path($config) . '/last_action.json',
                    json_encode(['mailips' => $mailipsResult], JSON_THROW_ON_ERROR)
                );
            }
        })(),
        'auto_fix_ip' => (function () use ($config, &$fixResult): void {
            $fixResult = ms_mail_apply_auto_fix($config, trim((string) ms_post('fix_ip', '')));
            @file_put_contents(
                ms_mail_path($config) . '/last_action.json',
                json_encode(['fix' => $fixResult], JSON_THROW_ON_ERROR)
            );
        })(),
        default => null,
    };

    return match ($action) {
        'scan' => 'ok',
        'scan_all' => 'ok_all',
        'scan_ip' => 'ok_ip',
        'rebuild_mailips' => ($mailipsResult['ok'] ?? false) ? 'ok_mailips' : 'err_mailips',
        'auto_fix_ip' => ($fixResult['ok'] ?? false) ? 'ok_fix' : 'err_fix',
        default => null,
    };
}

function ms_mail_scan_flash_message(string $code, array $config = []): string
{
    if ($code === 'ok_mailips') {
        $file = ms_mail_path($config) . '/last_action.json';
        if ($config !== [] && is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            $msg = $data['mailips']['message'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return $msg;
            }
        }

        return 'MailIPs et mailhelo régénérés, Exim rechargé.';
    }

    if ($code === 'ok_fix' || $code === 'err_fix') {
        $file = ms_mail_path($config) . '/last_action.json';
        if ($config !== [] && is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            $msg = $data['fix']['message'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return $msg;
            }
        }

        return $code === 'ok_fix' ? 'Correction automatique appliquée.' : 'Échec correction automatique.';
    }

    return match ($code) {
        'ok' => 'Analyse terminée.',
        'ok_all' => 'Analyse de toutes les IP terminée.',
        'ok_ip' => 'Analyse de l\'IP terminée.',
        'err_mailips' => 'Échec régénération mailips/mailhelo (root requis).',
        'ok_fix' => 'Correction automatique appliquée (voir détails ci-dessous).',
        'err_fix' => 'Échec correction automatique (root requis ou aucune action).',
        'csrf' => 'Jeton de sécurité invalide.',
        default => '',
    };
}
