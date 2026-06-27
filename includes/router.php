<?php

declare(strict_types=1);

function ms_handle_mail_api(array $config): bool
{
    if ((string) ms_get('api', '') !== 'mail') {
        return false;
    }

    header('Content-Type: application/json; charset=utf-8');
    ms_security_headers();

    $action = (string) ms_get('action', 'history');
    if ($action === 'history') {
        echo json_encode(['history' => ms_mail_load_history($config)], JSON_THROW_ON_ERROR);
        return true;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action'], JSON_THROW_ON_ERROR);
    return true;
}

function ms_handle_mail_page(array $config): bool
{
    if ((string) ms_get('page', '') !== 'mail') {
        return false;
    }

    if (defined('MEGASTATS_WHM') && MEGASTATS_WHM) {
        return false;
    }

    if (!($config['mail_enabled'] ?? true)) {
        http_response_code(404);
        echo 'Module mail désactivé.';
        return true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ms_post('mail_action', '') === 'scan') {
        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            header('Location: ' . ms_url($config['scriptname'], ['page' => 'mail', 'scan' => 'csrf']));
            exit;
        }

        ms_mail_run_scan($config);
        header('Location: ' . ms_url($config['scriptname'], ['page' => 'mail', 'scan' => 'ok']));
        exit;
    }

    ms_start_output_buffer($config);
    $view = ms_mail_build_page_view($config);
    $view['auth_mode'] = $config['auth_mode'] ?? 'password';
    $view['deployment'] = $config['deployment'] ?? 'standalone';
    $view['scan_flash'] = match ((string) ms_get('scan', '')) {
        'ok' => 'Analyse terminée.',
        'csrf' => 'Jeton de sécurité invalide.',
        default => '',
    };

    ms_render_template($view['mail_template'] ?? 'mail/overview', $view);
    return true;
}

function ms_render_mail_page_whm(array $config): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ms_post('mail_action', '') === 'scan') {
        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            header('Location: ' . ms_url($config['scriptname'], ['page' => 'mail', 'scan' => 'csrf']));
            exit;
        }
        ms_mail_run_scan($config);
        header('Location: ' . ms_url($config['scriptname'], ['page' => 'mail', 'scan' => 'ok']));
        exit;
    }

    ms_start_output_buffer($config);
    $view = ms_mail_build_page_view($config);
    $view['whm_embedded'] = true;
    $view['auth_mode'] = 'whm';
    $view['deployment'] = 'whm';
    $view['scan_flash'] = match ((string) ms_get('scan', '')) {
        'ok' => 'Analyse terminée.',
        'csrf' => 'Jeton de sécurité invalide.',
        default => '',
    };

    $assetsBase = ms_e($config['assets_base']);
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">' . "\n";
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">' . "\n";
    echo '<link href="' . $assetsBase . '/css/app.css" rel="stylesheet">' . "\n";
    echo '<script>document.documentElement.setAttribute("data-bs-theme", localStorage.getItem("megastats-theme") === "light" ? "light" : "dark");</script>' . "\n";
    echo '<div class="container-fluid py-3 ms-whm-wrap" data-bs-theme="dark">' . "\n";
    ms_render_template($view['mail_template'] ?? 'mail/overview', $view);
    echo "</div>\n";
}

function ms_handle_app_routes(array $config): bool
{
    if (ms_handle_update_web($config)) {
        return true;
    }
    if (ms_handle_update_api($config)) {
        return true;
    }
    if (ms_handle_mail_api($config)) {
        return true;
    }
    if (ms_handle_mail_page($config)) {
        return true;
    }
    if (ms_handle_config_page($config)) {
        return true;
    }

    return false;
}
