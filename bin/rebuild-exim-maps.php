#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Régénère /etc/mailips et /etc/mailhelo depuis cPanel (userips, userdomains, users).
 * Usage root : php bin/rebuild-exim-maps.php
 */

require dirname(__DIR__) . '/includes/bootstrap.php';

$result = ms_mail_rebuild_exim_config($config);

echo ($result['ok'] ? 'OK' : 'ERREUR') . ': ' . ($result['message'] ?? '') . "\n";

if (!empty($result['sources'])) {
    echo 'Sources: '
        . ($result['sources']['userdomains'] ? 'userdomains ' : '')
        . ($result['sources']['userips'] ? 'userips ' : '')
        . ($result['sources']['cpanel_users'] ? 'cpanel_users' : '')
        . "\n";
}

if (!($result['ok'] ?? false)) {
    exit(1);
}

exit(0);
