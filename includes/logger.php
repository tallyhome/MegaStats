<?php

declare(strict_types=1);

function ms_ensure_log_dir(string $path, int $mode = 0775): void
{
    if (!is_dir($path)) {
        mkdir($path, $mode, true);
    }
}

function ms_log(array $config, string $channel, string $message): void
{
    $path = rtrim($config['log_path'], '/\\');
    ms_ensure_log_dir($path);

    $file = match ($channel) {
        'auth' => $path . '/auth.log',
        'activity' => $path . '/activity.log',
        'error' => $path . '/error.log',
        default => $path . '/app.log',
    };

    $line = sprintf(
        "[%s] %s | IP: %s | %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($channel),
        ms_client_ip(),
        $message
    );

    file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}
