<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$error = ms_handle_login_request($config);

ms_render_template('login', [
    'error' => $error,
    'assets_base' => $config['assets_base'],
    'redirect' => (string) ms_get('redirect', ''),
]);
