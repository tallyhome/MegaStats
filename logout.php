<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (ms_is_whm_deployment($config)) {
    header('Location: /');
    exit;
}

ms_logout($config);
header('Location: login.php');
exit;
