<?php

declare(strict_types=1);

function ms_shell_cached(array $config, string $key, string $command): string
{
    if (empty($config['shell_cache_enabled'])) {
        return ms_shell($command);
    }

    $ttl = max(5, (int) ($config['shell_cache_ttl'] ?? 30));
    $cacheDir = rtrim((string) ($config['history_path'] ?? MEGASTATS_ROOT . '/storage/metrics'), '/\\') . '/cache';
    ms_ensure_log_dir($cacheDir);

    $file = $cacheDir . '/' . hash('sha256', $key) . '.cache';

    if (is_file($file) && (time() - (int) filemtime($file)) < $ttl) {
        $data = file_get_contents($file);
        return is_string($data) ? $data : '';
    }

    $output = ms_shell($command);
    file_put_contents($file, $output, LOCK_EX);

    return $output;
}

function ms_clear_shell_cache(array $config): void
{
    $cacheDir = rtrim((string) ($config['history_path'] ?? MEGASTATS_ROOT . '/storage/metrics'), '/\\') . '/cache';

    if (!is_dir($cacheDir)) {
        return;
    }

    foreach (glob($cacheDir . '/*.cache') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
