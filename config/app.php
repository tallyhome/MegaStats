<?php

declare(strict_types=1);

return [
    'name' => 'MegaStats',
    'version' => '3.3.0',
    'timezone' => 'UTC',
    'gzip' => 0,
    'refresh' => 1,
    'timeoffset' => 0,
    'log_path' => MEGASTATS_ROOT . '/storage/logs',
    'history_path' => MEGASTATS_ROOT . '/storage/metrics',
    'history_interval' => 60,
    // ~30 jours à 1 point/min (cron chaque minute)
    'history_max_points' => 43200,
    'history_chart_max_points' => 300,
    'chart_refresh_seconds' => 60,

    // Cron metrics collection (recommended)
    'cron_enabled' => true,
    'cron_token' => 'change-this-cron-token',
    // true = collecte aussi à chaque visite dashboard (en plus du cron)
    'cron_collect_on_dashboard' => true,

    // Cache shell output during a single dashboard request (seconds)
    'shell_cache_enabled' => true,
    'shell_cache_ttl' => 30,
];
