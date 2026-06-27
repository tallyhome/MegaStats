<?php

require __DIR__ . '/includes/bootstrap.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $token = (string) ms_get('token', '');
    if (!hash_equals((string) ($config['cron_token'] ?? ''), $token)) {
        ms_log($config, 'auth', 'Cron mail denied (bad token) from ' . ms_client_ip());
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden\n";
        exit;
    }
}

$result = ms_mail_run_cron($config);

header('Content-Type: text/plain; charset=utf-8');
echo 'scan=' . ($result['scan'] ? 'yes' : 'no') . "\n";
echo 'report=' . ($result['report'] ? 'yes' : 'no') . "\n";
