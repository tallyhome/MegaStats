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

function ms_update_flash_from_request(): string
{
    $state = (string) ms_get('update', '');
    if ($state === 'checked') {
        return 'Vérification des mises à jour terminée.';
    }
    if ($state === 'ok') {
        return 'Mise à jour installée. Rechargez la page (Ctrl+F5).';
    }
    if ($state === 'fail') {
        $msg = (string) ms_get('update_msg', '');
        if ($msg !== '') {
            $decoded = base64_decode($msg, true);
            if (is_string($decoded) && $decoded !== '') {
                return 'Échec mise à jour : ' . $decoded;
            }
        }

        return 'Échec de la mise à jour. Consultez /opt/megastats ou lancez ./whm/update.sh en SSH.';
    }
    if ($state === 'denied') {
        return 'Mise à jour réservée à la session WHM root.';
    }

    return '';
}

function ms_json_exit(array $payload, int $code = 200): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    ms_security_headers();
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
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

    @set_time_limit(300);

    $cmd = 'bash ' . escapeshellarg($script) . ' 2>&1';
    $output = [];
    $code = 0;

    if (function_exists('exec') && !ms_function_disabled('exec')) {
        exec($cmd, $output, $code);
        $text = implode("\n", $output);
    } else {
        $text = (string) shell_exec($cmd);
        $code = ($text !== '' && (str_contains($text, 'Mise à jour terminée') || str_contains($text, 'Version installée'))) ? 0 : 1;
    }

    ms_log($config, 'activity', 'Update run exit=' . $code);

    return [
        'ok' => $code === 0,
        'output' => $text,
        'exit_code' => $code,
    ];
}

function ms_function_disabled(string $name): bool
{
    $disabled = ini_get('disable_functions');
    if (!is_string($disabled) || $disabled === '') {
        return false;
    }

    return in_array($name, array_map('trim', explode(',', $disabled)), true);
}

function ms_handle_update_web(array $config): bool
{
    if (!(defined('MEGASTATS_WHM') && MEGASTATS_WHM)) {
        return false;
    }

    $action = (string) (ms_post('update_action', '') ?: ms_get('update_action', ''));
    if ($action === '') {
        return false;
    }

    ms_whm_require_access($config);

    $scriptname = (string) ($config['scriptname'] ?? ms_whm_request_path());

    if (!ms_update_can_run($config)) {
        header('Location: ' . ms_url($scriptname, ['update' => 'denied']));
        exit;
    }

    if ($action === 'check') {
        ms_update_status($config, true);
        header('Location: ' . ms_url($scriptname, ['update' => 'checked']));
        exit;
    }

    if ($action === 'run') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . ms_url($scriptname));
            exit;
        }

        $result = ms_update_run($config);
        $params = ['update' => $result['ok'] ? 'ok' : 'fail'];
        if (!$result['ok']) {
            $params['update_msg'] = base64_encode(mb_substr((string) ($result['output'] ?? ''), 0, 1500));
        }
        header('Location: ' . ms_url($scriptname, $params));
        exit;
    }

    return false;
}

function ms_handle_update_api(array $config): bool
{
    if ((string) ms_get('api', '') !== 'update') {
        return false;
    }

    if (defined('MEGASTATS_WHM') && MEGASTATS_WHM) {
        ms_whm_require_access($config);
    }

    $action = (string) ms_get('action', 'check');

    if ($action === 'check') {
        ms_json_exit(ms_update_status($config, (bool) ms_get('refresh')));
    }

    if ($action === 'run') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            ms_json_exit(['ok' => false, 'error' => 'POST required'], 405);
        }

        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            ms_json_exit(['ok' => false, 'error' => 'CSRF'], 403);
        }

        $result = ms_update_run($config);
        $result['status'] = ms_update_status($config, true);
        ms_json_exit($result, $result['ok'] ? 200 : 500);
    }

    ms_json_exit(['ok' => false, 'error' => 'Unknown action'], 400);
}
