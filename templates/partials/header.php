<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ms_e($page_title ?? 'MegaStats') ?></title>
    <?php if (!empty($refresh_seconds)): ?>
        <meta http-equiv="refresh" content="<?= (int) $refresh_seconds ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ms_e($assets_base) ?>/css/app.css" rel="stylesheet">
</head>
<body class="ms-body">
<nav class="navbar navbar-expand-lg border-bottom mb-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="index.php">
            <i class="bi bi-speedometer2 me-2"></i>MegaStats
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($user)): ?>
                <span class="navbar-text small d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i><?= ms_e($user) ?>
                    <?php if (($auth_mode ?? '') === 'whm'): ?>
                        <span class="badge text-bg-primary ms-1">WHM</span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Thème clair / sombre">
                <i class="bi bi-moon-stars"></i>
            </button>
            <?php if (!empty($donate_url)): ?>
                <a href="<?= ms_e($donate_url) ?>" class="btn btn-sm btn-outline-warning" target="_blank" rel="noopener" title="Faire un don — PayPal">
                    <i class="bi bi-heart me-1"></i>Don
                </a>
            <?php endif; ?>
            <?php if (($auth_mode ?? 'password') === 'password' || ($auth_mode ?? '') === 'both'): ?>
                <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container-fluid pb-4">
