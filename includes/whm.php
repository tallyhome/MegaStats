<?php

declare(strict_types=1);

function ms_whm_request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : false;

    if (is_string($path) && $path !== '' && str_contains($path, 'megastats')) {
        return $path;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script !== '' && str_contains($script, 'megastats')) {
        return $script;
    }

    return '/cgi/megastats/index.cgi';
}

function ms_whm_has_cpsess(): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    return is_string($uri) && preg_match('#/cpsess\d+/#', $uri) === 1;
}

function ms_whm_init(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $configInc = '/usr/local/cpanel/whostmgr/docroot/inc/config.inc.php';
    if (is_file($configInc)) {
        require_once $configInc;
    }
}

function ms_whm_user(): ?string
{
    foreach (
        [
            $_SERVER['REMOTE_USER'] ?? '',
            $_ENV['REMOTE_USER'] ?? '',
            $_SERVER['REDIRECT_REMOTE_USER'] ?? '',
            $_ENV['REDIRECT_REMOTE_USER'] ?? '',
        ] as $user
    ) {
        if (is_string($user) && $user !== '') {
            return $user;
        }
    }

    global $authuser;

    if (!empty($authuser) && is_string($authuser)) {
        return $authuser;
    }

    // AppConfig + cpsess : cpsrvd a deja valide la session WHM avant d executer le CGI PHP.
    if (ms_whm_has_cpsess()) {
        return 'root';
    }

    return null;
}

function ms_whm_user_allowed(array $config, string $user): bool
{
    $allowed = $config['whm_acls'] ?? ['all'];

    if ($allowed === [] || in_array('all', $allowed, true)) {
        return true;
    }

    return in_array($user, $allowed, true);
}

function ms_whm_require_access(array $config): void
{
    $user = ms_whm_user();
    if ($user === null || !ms_whm_user_allowed($config, $user)) {
        ms_log($config, 'auth', 'WHM access denied from ' . ms_client_ip());
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>MegaStats</title></head><body style="font-family:sans-serif;padding:2rem;">';
        echo '<h1>MegaStats — accès WHM requis</h1>';
        echo '<p>Ouvrez cette application <strong>depuis WHM</strong> (port 2087), connecté en root.</p>';
        echo '<p>Utilisez la recherche WHM : <strong>MegaStats</strong></p>';
        echo '<p>Ne pas utiliser l’URL directe sans session WHM (<code>cpsess…</code>).</p>';
        echo '</body></html>';
        exit;
    }
}
