<?php

declare(strict_types=1);

function ms_alert_status(float $value, float $warning, float $critical): string
{
    if ($value >= $critical) {
        return 'critical';
    }
    if ($value >= $warning) {
        return 'warning';
    }
    return 'ok';
}

function ms_alert_bootstrap_class(string $status): string
{
    return match ($status) {
        'critical' => 'danger',
        'warning' => 'warning',
        default => 'success',
    };
}

function ms_evaluate_alerts(array $config, array $metrics): array
{
    $thresholds = $config['alerts'] ?? [];
    $alerts = [];

    if ($metrics['cpu'] !== null) {
        $status = ms_alert_status((float) $metrics['cpu'], (float) ($thresholds['cpu_warning'] ?? 70), (float) ($thresholds['cpu_critical'] ?? 90));
        if ($status !== 'ok') {
            $alerts[] = [
                'key' => 'cpu',
                'status' => $status,
                'icon' => 'bi-cpu',
                'message' => sprintf('CPU usage: %.1f%%', $metrics['cpu']),
            ];
        }
    }

    if ($metrics['mem_used_pct'] !== null) {
        $status = ms_alert_status((float) $metrics['mem_used_pct'], (float) ($thresholds['ram_warning'] ?? 75), (float) ($thresholds['ram_critical'] ?? 90));
        if ($status !== 'ok') {
            $alerts[] = [
                'key' => 'ram',
                'status' => $status,
                'icon' => 'bi-memory',
                'message' => sprintf('RAM usage: %.1f%%', $metrics['mem_used_pct']),
            ];
        }
    }

    if ($metrics['load1'] !== null) {
        $status = ms_alert_status((float) $metrics['load1'], (float) ($thresholds['load_warning'] ?? 1), (float) ($thresholds['load_critical'] ?? 5));
        if ($status !== 'ok') {
            $alerts[] = [
                'key' => 'load',
                'status' => $status,
                'icon' => 'bi-activity',
                'message' => sprintf('Load average (1 min): %.2f', $metrics['load1']),
            ];
        }
    }

    if ($metrics['disk_max_pct'] !== null && ($metrics['disk_alerts'] ?? []) === []) {
        $status = ms_alert_status((float) $metrics['disk_max_pct'], (float) ($thresholds['disk_warning'] ?? 75), (float) ($thresholds['disk_critical'] ?? 90));
        if ($status !== 'ok') {
            $alerts[] = [
                'key' => 'disk',
                'status' => $status,
                'icon' => 'bi-hdd',
                'message' => sprintf('Disk usage (max): %d%%', (int) $metrics['disk_max_pct']),
            ];
        }
    }

    if ($metrics['network_today_mb'] !== null && (float) $metrics['network_today_mb'] > 0) {
        $status = ms_alert_status((float) $metrics['network_today_mb'], (float) ($thresholds['network_warning_mb'] ?? 800), (float) ($thresholds['network_critical_mb'] ?? 1000));
        if ($status !== 'ok') {
            $alerts[] = [
                'key' => 'network',
                'status' => $status,
                'icon' => 'bi-graph-up-arrow',
                'message' => sprintf('Network today: %.0f MB', $metrics['network_today_mb']),
            ];
        }
    }

    foreach ($metrics['disk_alerts'] ?? [] as $diskAlert) {
        $alerts[] = $diskAlert;
    }

    usort($alerts, static function (array $a, array $b): int {
        $order = ['critical' => 0, 'warning' => 1, 'ok' => 2];
        return ($order[$a['status']] ?? 3) <=> ($order[$b['status']] ?? 3);
    });

    return $alerts;
}

function ms_apply_disk_alert_levels(array $disks, array $thresholds): array
{
    $warn = (float) ($thresholds['disk_warning'] ?? 75);
    $crit = (float) ($thresholds['disk_critical'] ?? 90);

    foreach ($disks as &$disk) {
        $status = ms_alert_status((float) $disk['percent'], $warn, $crit);
        $disk['alert'] = $status;
        $disk['level'] = ms_alert_bootstrap_class($status);
    }
    unset($disk);

    return $disks;
}

function ms_apply_load_alert_levels(array $loads, array $thresholds): array
{
    $warn = (float) ($thresholds['load_warning'] ?? 1);
    $crit = (float) ($thresholds['load_critical'] ?? 5);

    foreach ($loads as &$load) {
        $status = ms_alert_status((float) $load['value'], $warn, $crit);
        $load['alert'] = $status;
        $load['level'] = ms_alert_bootstrap_class($status);
        $load['percent'] = ms_load_percent((float) $load['value']);
    }
    unset($load);

    return $loads;
}
