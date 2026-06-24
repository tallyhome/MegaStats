<?php

declare(strict_types=1);

function ms_session_start(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name($config['session_name'] ?? 'MEGASTATSSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => dirname(ms_script_name()) ?: '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function ms_is_whm_deployment(array $config): bool
{
    return ($config['deployment'] ?? '') === 'whm' || (defined('MEGASTATS_WHM') && MEGASTATS_WHM);
}

function ms_ip_allowed(array $config): bool
{
    $whitelist = $config['ip_whitelist'] ?? [];

    if ($whitelist === []) {
        return true;
    }

    return in_array(ms_client_ip(), $whitelist, true);
}

function ms_is_authenticated(array $config): bool
{
    $mode = $config['auth_mode'] ?? 'none';

    if ($mode === 'none') {
        return true;
    }

    if ($mode === 'whm') {
        $user = ms_whm_user();

        return $user !== null && ms_whm_user_allowed($config, $user);
    }

    if (!ms_ip_allowed($config)) {
        return false;
    }

    if ($mode === 'ip') {
        return true;
    }

    if (empty($_SESSION['ms_user']) || empty($_SESSION['ms_login_time'])) {
        return false;
    }

    $timeout = (int) ($config['session_timeout'] ?? 3600);
    if (time() - (int) $_SESSION['ms_login_time'] > $timeout) {
        ms_log($config, 'auth', 'Session expired for user ' . ($_SESSION['ms_user'] ?? 'unknown'));
        ms_logout($config, false);
        return false;
    }

    $_SESSION['ms_login_time'] = time();
    return true;
}

function ms_attempt_login(array $config, string $username, string $password): bool
{
    if (!ms_ip_allowed($config)) {
        ms_log($config, 'auth', 'Login denied: IP not whitelisted (' . ms_client_ip() . ')');
        return false;
    }

    $validUser = hash_equals((string) ($config['username'] ?? ''), $username);
    $validPass = password_verify($password, (string) ($config['password_hash'] ?? ''));

    if (!$validUser || !$validPass) {
        ms_log($config, 'auth', 'Failed login attempt for user ' . $username);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['ms_user'] = $username;
    $_SESSION['ms_login_time'] = time();
    ms_log($config, 'auth', 'Successful login for user ' . $username);
    ms_log($config, 'activity', 'User logged in');
    return true;
}

function ms_logout(array $config, bool $log = true): void
{
    if ($log) {
        ms_log($config, 'auth', 'Logout for user ' . ($_SESSION['ms_user'] ?? 'unknown'));
        ms_log($config, 'activity', 'User logged out');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function ms_require_auth(array $config): void
{
    ms_security_headers();

    $mode = $config['auth_mode'] ?? 'none';
    if ($mode === 'none') {
        return;
    }

    if (ms_is_authenticated($config)) {
        return;
    }

    if ($mode === 'whm') {
        ms_whm_require_access($config);
        return;
    }

    $redirect = urlencode(ms_script_name());
    header('Location: login.php?redirect=' . $redirect);
    exit;
}

function ms_handle_login_request(array $config): ?string
{
    ms_security_headers();

    if (($config['auth_mode'] ?? 'none') === 'none') {
        header('Location: index.php');
        exit;
    }

    if (ms_is_authenticated($config)) {
        header('Location: index.php');
        exit;
    }

    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!ms_verify_csrf($config)) {
            $error = 'Invalid security token. Please try again.';
            ms_log($config, 'auth', 'CSRF validation failed on login');
        } else {
            $username = trim((string) ms_post('username', ''));
            $password = (string) ms_post('password', '');

            if (ms_attempt_login($config, $username, $password)) {
                $target = (string) ms_get('redirect', 'index.php');
                if ($target !== 'index.php') {
                    $target = 'index.php';
                }
                header('Location: ' . $target);
                exit;
            }

            $error = 'Invalid username or password.';
        }
    }

    return $error;
}
