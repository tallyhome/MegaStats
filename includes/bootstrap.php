<?php

declare(strict_types=1);

if (!defined('MEGASTATS_ROOT')) {
    define('MEGASTATS_ROOT', dirname(__DIR__));
}

if (!defined('MEGASTATS_WHM')) {
    $whmMarker = MEGASTATS_ROOT . '/.whm-deployment';
    $whmInstallPath = '/usr/local/cpanel/whostmgr/docroot/cgi/megastats';
    if (is_file($whmMarker) || realpath(MEGASTATS_ROOT) === realpath($whmInstallPath)) {
        define('MEGASTATS_WHM', true);
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require MEGASTATS_ROOT . '/includes/helpers.php';
require MEGASTATS_ROOT . '/includes/logger.php';
require MEGASTATS_ROOT . '/includes/cache.php';
require MEGASTATS_ROOT . '/includes/whm.php';
require MEGASTATS_ROOT . '/includes/auth.php';
require MEGASTATS_ROOT . '/includes/alerts.php';
require MEGASTATS_ROOT . '/includes/metrics.php';
require MEGASTATS_ROOT . '/includes/monitoring.php';
require MEGASTATS_ROOT . '/includes/requests.php';
require MEGASTATS_ROOT . '/includes/maintenance.php';

$config = array_merge(
    require MEGASTATS_ROOT . '/config/app.php',
    require MEGASTATS_ROOT . '/config/monitoring.php',
    require MEGASTATS_ROOT . '/config/security.php',
    ['alerts' => require MEGASTATS_ROOT . '/config/alerts.php']
);

if (is_file(MEGASTATS_ROOT . '/config/distribution.php')) {
    $config = array_merge($config, require MEGASTATS_ROOT . '/config/distribution.php');
}

if (defined('MEGASTATS_WHM') && MEGASTATS_WHM && is_file(MEGASTATS_ROOT . '/config/app.whm.php')) {
    $config = array_merge($config, require MEGASTATS_ROOT . '/config/app.whm.php');
}

date_default_timezone_set($config['timezone'] ?? 'UTC');

ms_ensure_log_dir($config['log_path']);
ms_ensure_log_dir($config['history_path'] ?? MEGASTATS_ROOT . '/storage/metrics');

ob_start();
$toppass = system("grep password /root/.my.cnf|cut -d '\"' -f2");
ob_clean();
ob_start();

$config['my_pass'] = is_string($toppass) ? trim($toppass) : '';
if (defined('MEGASTATS_WHM') && MEGASTATS_WHM) {
    $config['scriptname'] = ms_whm_request_path();
    $config['assets_base'] = (string) ($config['assets_base'] ?? '/cgi/megastats/assets');
} else {
    $config['scriptname'] = ms_script_name();
    $config['assets_base'] = ms_assets_base($config['scriptname']);
}
$config['timestamp'] = time() + ((int) $config['timeoffset'] * 3600);
$config['localtime'] = date('g:i a, M j', $config['timestamp']);
$config['shorttime'] = date('g:i a', $config['timestamp']);

$mysqlreport = MEGASTATS_ROOT . '/mysqlreport';
$config['mysql_com'] = '';
$config['mysql_com2'] = '';

if ((int) $config['mysql_mon'] === 1) {
    $config['mysql_com'] = sprintf(
        'env HOME=%s env TERM=xterm mytop -u %s -p %s -d %s -b --nocolor',
        escapeshellarg($config['userhome']),
        escapeshellarg($config['my_user']),
        escapeshellarg($config['my_pass']),
        escapeshellarg($config['my_db'])
    );
} elseif ((int) $config['mysql_mon'] === 2) {
    $config['mysql_com'] = sprintf(
        '%s --user %s --password %s --no-mycnf 2>&1',
        escapeshellarg($mysqlreport),
        escapeshellarg($config['my_user']),
        escapeshellarg($config['my_pass'])
    );
    $config['mysql_com2'] = sprintf(
        '%s --all --tab --user %s --password %s --no-mycnf',
        escapeshellarg($mysqlreport),
        escapeshellarg($config['my_user']),
        escapeshellarg($config['my_pass'])
    );
}

set_exception_handler(static function (Throwable $e) use ($config): void {
    ms_log($config, 'error', 'Uncaught exception: ' . $e->getMessage());
    http_response_code(500);
    echo 'An internal error occurred.';
    exit;
});

set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($config): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    ms_log($config, 'error', "{$message} in {$file}:{$line}");
    return true;
});

if (!(defined('MEGASTATS_WHM') && MEGASTATS_WHM)) {
    ms_session_start($config);
}
