<?php

declare(strict_types=1);

return [
    'deployment' => 'whm',
    'log_path' => '/var/cpanel/megastats/logs',
    'history_path' => '/var/cpanel/megastats/metrics',
    'auth_mode' => 'whm',
    // WHM users allowed (AppConfig acls=root limits the menu; this limits runtime access)
    'whm_acls' => ['all'],
    'userhome' => '/root',
    'scriptname' => '/cgi/megastats/index.cgi',
    'assets_base' => '/cgi/megastats/assets',
    'public_entry' => '/cgi/megastats/index.cgi',
    'refresh' => 0,
    'cron_collect_on_dashboard' => true,
    'shell_cache_enabled' => true,
    // WHM fournit l auth — pas de session PHP (evite conflit cookies /cgi avec cpsess)
    'csrf_enabled' => false,
    'history_max_points' => 43200,
    'history_chart_max_points' => 300,
    'tmp_clear_enabled' => true,
    'tmp_clear_min_age_seconds' => 3600,
];
