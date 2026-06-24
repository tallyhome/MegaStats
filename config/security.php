<?php

declare(strict_types=1);

return [
    // none | password | ip | both
    'auth_mode' => 'password',
    'username' => 'admin',
    // Default password: changeme — change this hash after install.
    'password_hash' => '$2y$10$xC3goa2Ppiq0yDC8gXBwxul.Sc2dwRpecV6eJdnigTgZC0WDMksxW',
    'session_timeout' => 3600,
    'session_name' => 'MEGASTATSSESSID',
    // Empty = allow all IPs (when mode uses IP check). Example: ['127.0.0.1', '82.66.185.78']
    'ip_whitelist' => [],
    'csrf_enabled' => true,
];
