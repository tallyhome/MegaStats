<?php

declare(strict_types=1);

function ms_can_clear_tmp(array $config): bool
{
    if (empty($config['tmp_clear_enabled'])) {
        return false;
    }

    if (ms_is_whm_deployment($config)) {
        $user = ms_whm_user();

        return $user !== null && ms_whm_user_allowed($config, $user);
    }

    return ms_is_authenticated($config);
}

function ms_clear_tmp_directory(array $config): array
{
    $tmpDir = '/tmp';
    $minAge = max(0, (int) ($config['tmp_clear_min_age_seconds'] ?? 3600));
    $now = time();
    $deleted = 0;
    $skipped = 0;
    $errors = [];

    if (!is_dir($tmpDir) || !is_readable($tmpDir)) {
        return ['ok' => false, 'deleted' => 0, 'skipped' => 0, 'errors' => ['/tmp inaccessible']];
    }

    $handle = @opendir($tmpDir);
    if ($handle === false) {
        return ['ok' => false, 'deleted' => 0, 'skipped' => 0, 'errors' => ['Impossible d\'ouvrir /tmp']];
    }

    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        if (
            str_starts_with($entry, 'sess_')
            || str_starts_with($entry, 'systemd-private')
            || $entry === 'lost+found'
            || $entry === '.X11-unix'
            || $entry === '.ICE-unix'
            || $entry === '.font-unix'
            || $entry === '.Test-unix'
        ) {
            $skipped++;
            continue;
        }

        $path = $tmpDir . '/' . $entry;
        if (!is_file($path)) {
            $skipped++;
            continue;
        }

        $mtime = @filemtime($path);
        if ($minAge > 0 && $mtime !== false && ($now - $mtime) < $minAge) {
            $skipped++;
            continue;
        }

        if (@unlink($path)) {
            $deleted++;
        } else {
            $errors[] = $entry;
        }
    }

    closedir($handle);
    ms_log($config, 'activity', sprintf('Clear /tmp: %d deleted, %d skipped', $deleted, $skipped));

    return [
        'ok' => $errors === [],
        'deleted' => $deleted,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
