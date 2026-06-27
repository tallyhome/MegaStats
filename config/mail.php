<?php

declare(strict_types=1);

return [
    'mail_enabled' => true,
    // Données scans (WHM : /var/cpanel/megastats/mail)
    'mail_path' => '',
    // Notifications (rapport quotidien + alertes RBL)
    'mail_report_email' => '',
    'mail_alert_email' => '',
    'mail_report_hour' => 7,
    'mail_scan_hour' => 6,
    'mail_domains' => [],
    'mail_sending_ips' => [],
    'mail_dkim_selectors' => ['default', 'google', 'selector1', 'k1', 's1', 'cpanel'],
    'mail_smtp_host' => '127.0.0.1',
    'mail_smtp_port' => 25,
    'mail_helo_name' => '',
    'mail_history_days' => 90,
    // Microsoft SNDS (https://sendersupport.olc.protection.outlook.com/snds/)
    'mail_snds_key' => '',
    'mail_snds_account' => '',
    // Tests inbox : adresses de test optionnelles (sinon test MX uniquement)
    'mail_test_inboxes' => [
        'gmail' => '',
        'outlook' => '',
        'yahoo' => '',
        'orange' => '',
    ],
    'mail_test_mx_hosts' => [
        'gmail' => 'gmail-smtp-in.l.google.com',
        'outlook' => 'outlook-com.olc.protection.outlook.com',
        'yahoo' => 'mta7.am0.yahoodns.net',
        'orange' => 'smtp-in.orange.fr',
    ],
    // Chemin script mise à jour (WHM)
    'update_script' => '/opt/megastats/whm/update.sh',
    'update_git_repo' => 'tallyhome/MegaStats',
];
