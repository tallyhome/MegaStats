<?php

define('MEGASTATS_WHM', true);

$configInc = '/usr/local/cpanel/whostmgr/docroot/inc/config.inc.php';
$whmLib = '/usr/local/cpanel/php/WHM.php';

if (is_file($configInc)) {
    require_once $configInc;
}

if (isset($_GET['whmtest']) && (string) $_GET['whmtest'] === '1') {
    require_once __DIR__ . '/includes/whm.php';
    header('Content-Type: text/html; charset=utf-8');
    global $authuser;
    echo '<pre style="font:14px monospace;padding:20px">';
    echo "MegaStats WHM auth test\n";
    echo 'PHP_SAPI=' . PHP_SAPI . "\n";
    echo 'authuser=' . (isset($authuser) && is_string($authuser) && $authuser !== '' ? $authuser : '(unset)') . "\n";
    echo 'REMOTE_USER=' . ($_SERVER['REMOTE_USER'] ?? '(empty)') . "\n";
    echo 'cpsess=' . (ms_whm_has_cpsess() ? 'yes' : 'no') . "\n";
    echo 'whm_user=' . (ms_whm_user() ?? '(null)') . "\n";
    echo 'REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '?') . "\n";
    echo 'scriptname=' . ms_whm_request_path() . "\n";
    echo "\nAttendu : cpsess=yes et whm_user=root\n";
    echo '</pre>';
    exit;
}

require __DIR__ . '/includes/bootstrap.php';

if (ms_handle_metrics_api($config)) {
    exit;
}

if (ms_handle_request($config)) {
    exit;
}

if (!is_file($whmLib)) {
    ms_whm_require_access($config);
    ms_start_output_buffer($config);
    $view = ms_build_dashboard($config);
    $view['auth_mode'] = 'whm';
    $view['deployment'] = 'whm';
    ms_render_template('dashboard', $view);
    exit;
}

require_once $whmLib;

ms_whm_require_access($config);

WHM::header('MegaStats', 0, 0);

$assetsBase = ms_e($config['assets_base']);

echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">' . "\n";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">' . "\n";
echo '<link href="' . $assetsBase . '/css/app.css" rel="stylesheet">' . "\n";
echo '<div class="container-fluid py-3 ms-whm-wrap">' . "\n";

ms_start_output_buffer($config);

$view = ms_build_dashboard($config);
$view['auth_mode'] = 'whm';
$view['deployment'] = 'whm';
$view['whm_embedded'] = true;
$view['user'] = ms_whm_user() ?? 'root';

ms_render_template('dashboard', $view);

echo "</div>\n";

WHM::footer();

echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>' . "\n";
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>' . "\n";
echo '<script src="' . $assetsBase . '/js/charts.js"></script>' . "\n";
echo '<script src="' . $assetsBase . '/js/theme.js"></script>' . "\n";
echo '<script src="' . $assetsBase . '/js/app.js"></script>' . "\n";
