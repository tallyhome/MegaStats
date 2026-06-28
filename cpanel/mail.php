<?php

define('MEGASTATS_CPANEL', true);

require __DIR__ . '/includes/bootstrap.php';
require MEGASTATS_ROOT . '/includes/cpanel.php';

if (!($config['mail_enabled'] ?? true)) {
    http_response_code(404);
    echo 'Module mail désactivé.';
    exit;
}

$user = ms_cpanel_require_user($config);
$view = ms_cpanel_build_mail_view($config, $user);

header('Content-Type: text/html; charset=utf-8');
ms_security_headers();

$assetsBase = ms_e($view['assets_base']);
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ms_e($view['page_title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $assetsBase ?>/css/app.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<div class="container py-4">
<?php ms_render_template('cpanel/mail', $view); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
