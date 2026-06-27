<?php

declare(strict_types=1);

function ms_version_compare(string $current, string $latest): int
{
    return version_compare($current, $latest);
}

function ms_update_fetch_latest(array $config): ?string
{
    $repo = (string) ($config['update_git_repo'] ?? $config['git_repo'] ?? '');
    if (preg_match('#github\.com/([^/]+/[^/.]+)#', $repo, $m)) {
        $slug = $m[1];
    } elseif (str_contains($repo, '/')) {
        $slug = preg_replace('#\.git$#', '', $repo);
    } else {
        $slug = (string) ($config['update_git_repo'] ?? 'tallyhome/MegaStats');
    }

    $url = 'https://api.github.com/repos/' . $slug . '/tags?per_page=5';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: MegaStats-Update-Checker\r\nAccept: application/vnd.github+json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return null;
    }

    $tags = json_decode($raw, true);
    if (!is_array($tags) || $tags === []) {
        return null;
    }

    $versions = [];
    foreach ($tags as $tag) {
        $name = ltrim((string) ($tag['name'] ?? ''), 'vV');
        if ($name !== '' && preg_match('/^\d+\.\d+\.\d+/', $name)) {
            $versions[] = $name;
        }
    }

    if ($versions === []) {
        return null;
    }

    usort($versions, 'version_compare');
    return end($versions) ?: null;
}

function ms_update_status(array $config, bool $forceRefresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$forceRefresh) {
        return $cache;
    }

    $current = (string) ($config['version'] ?? '0.0.0');
    $latest = ms_update_fetch_latest($config);
    $available = $latest !== null && ms_version_compare($current, $latest) < 0;

    $cache = [
        'current' => $current,
        'latest' => $latest,
        'update_available' => $available,
        'repo_url' => 'https://github.com/' . ($config['update_git_repo'] ?? 'tallyhome/MegaStats'),
    ];

    return $cache;
}

function ms_update_can_run(array $config): bool
{
    if (!(defined('MEGASTATS_WHM') && MEGASTATS_WHM)) {
        return false;
    }

    $user = ms_whm_user();
    return $user === 'root' || $user === null;
}

function ms_update_script_path(array $config): string
{
    $candidates = array_values(array_filter([
        (string) ($config['update_script'] ?? ''),
        '/opt/megastats/whm/update.sh',
        MEGASTATS_ROOT . '/whm/update.sh',
    ]));

    foreach ($candidates as $path) {
        if (is_file($path)) {
            if (!is_executable($path)) {
                @chmod($path, 0755);
            }

            return $path;
        }
    }

    return '/opt/megastats/whm/update.sh';
}

function ms_update_run(array $config): array
{
    if (!ms_update_can_run($config)) {
        return ['ok' => false, 'output' => 'Mise à jour réservée à WHM root.'];
    }

    $script = ms_update_script_path($config);
    if (!is_file($script)) {
        return [
            'ok' => false,
            'output' => 'Script introuvable : ' . $script . "\n"
                . "Réinstallez : git clone https://github.com/tallyhome/MegaStats.git /opt/megastats\n"
                . 'puis : chmod +x whm/*.sh && ./whm/update.sh',
        ];
    }

    if (!is_executable($script)) {
        @chmod($script, 0755);
    }

    $cmd = 'bash ' . escapeshellarg($script) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);

    ms_log($config, 'activity', 'Update run exit=' . $code);

    return [
        'ok' => $code === 0,
        'output' => implode("\n", $output),
        'exit_code' => $code,
    ];
}

function ms_handle_update_api(array $config): bool
{
    if ((string) ms_get('api', '') !== 'update') {
        return false;
    }

    header('Content-Type: application/json; charset=utf-8');
    ms_security_headers();

    $action = (string) ms_get('action', 'check');

    if ($action === 'check') {
        echo json_encode(ms_update_status($config, (bool) ms_get('refresh')), JSON_THROW_ON_ERROR);
        return true;
    }

    if ($action === 'run') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'POST required'], JSON_THROW_ON_ERROR);
            return true;
        }

        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF'], JSON_THROW_ON_ERROR);
            return true;
        }

        $result = ms_update_run($config);
        $result['status'] = ms_update_status($config, true);
        echo json_encode($result, JSON_THROW_ON_ERROR);
        return true;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action'], JSON_THROW_ON_ERROR);
    return true;
}
