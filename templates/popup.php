<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ms_e($title) ?> · MegaStats</title>
    <?= $meta ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= ms_e($assets_base) ?>/css/app.css" rel="stylesheet">
</head>
<body class="ms-popup d-flex flex-column vh-100">
<header class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-body-secondary">
    <strong><?= ms_e($title) ?> @ <?= ms_e($shorttime) ?></strong>
    <div><?= $buttons_html ?></div>
</header>
<div class="flex-grow-1 overflow-auto p-2">
    <pre class="ms-pre mb-0"><?= $out ?></pre>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ms_e($assets_base) ?>/js/app.js"></script>
</body>
</html>
