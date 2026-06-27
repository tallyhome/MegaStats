<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

ms_require_auth($config);

if (ms_handle_metrics_api($config)) {
    exit;
}

if (ms_handle_app_routes($config)) {
    exit;
}

if (ms_handle_request($config)) {
    exit;
}

ms_start_output_buffer($config);

$view = ms_build_dashboard($config);
$view['auth_mode'] = $config['auth_mode'] ?? 'password';

ms_render_template('dashboard', $view);
