<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · MegaStats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ms_e($assets_base) ?>/css/app.css" rel="stylesheet">
</head>
<body class="ms-login d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow-lg ms-login-card">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-speedometer2 fs-1 text-primary"></i>
            <h1 class="h4 mt-2 mb-0">MegaStats</h1>
            <p class="text-secondary small">Server monitoring panel</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= ms_e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php<?= !empty($redirect) ? '?redirect=' . ms_e($redirect) : '' ?>" autocomplete="off">
            <?= ms_csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
        <p class="text-secondary small mt-3 mb-0 text-center">Default: admin / changeme</p>
    </div>
</div>
<script src="<?= ms_e($assets_base) ?>/js/theme.js"></script>
</body>
</html>
