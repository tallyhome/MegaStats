<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $token = (string) ms_get('token', '');
    if (!hash_equals((string) ($config['cron_token'] ?? ''), $token)) {
        ms_log($config, 'auth', 'Cron access denied (bad token) from ' . ms_client_ip());
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden\n";
        exit;
    }
}

$result = ms_collect_and_record_metrics($config, $isCli);

if ($result['recorded']) {
    ms_log($config, 'activity', 'Cron metrics recorded (' . ($result['history_points'] ?? 0) . ' points)');
}

header('Content-Type: text/plain; charset=utf-8');
echo ($result['recorded'] ? 'OK recorded' : 'OK skipped (interval)') . "\n";
echo 'points=' . (int) ($result['history_points'] ?? 0) . "\n";
echo 'writable=' . (($result['writable'] ?? false) ? 'yes' : 'no') . "\n";
echo 'clients=' . (int) ($result['snapshot']['connected_users'] ?? 0) . "\n";
echo 'tcp_established=' . (int) ($result['client_stats']['established_tcp'] ?? 0) . "\n";
