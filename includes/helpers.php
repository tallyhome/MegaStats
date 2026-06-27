<?php

declare(strict_types=1);

function ms_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ms_get(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function ms_post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function ms_script_name(): string
{
    return $_SERVER['SCRIPT_NAME'] ?? '/index.php';
}

function ms_assets_base(string $scriptname): string
{
    $dir = rtrim(dirname($scriptname), '/\\');
    return ($dir === '' || $dir === '.') ? '/assets' : $dir . '/assets';
}

function ms_shell(string $command): string
{
    $output = shell_exec($command);
    return is_string($output) ? trim($output) : '';
}

function ms_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function ms_url(string $scriptname, array $params = []): string
{
    if ($params === []) {
        return $scriptname;
    }

    return $scriptname . '?' . http_build_query($params);
}

/**
 * URL API fiable en WHM (préserve cpsess depuis la requête courante).
 */
function ms_api_url(array $config, array $params): string
{
    $scriptname = (string) ($config['scriptname'] ?? ms_script_name());

    if (function_exists('ms_is_whm_deployment') && ms_is_whm_deployment($config)) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (is_string($uri) && preg_match('#(/cpsess\d+/[^?]*megastats[^?]*)#', $uri, $m)) {
            $scriptname = $m[1];
        } elseif (function_exists('ms_whm_request_path')) {
            $scriptname = ms_whm_request_path();
        }
    }

    return ms_url($scriptname, $params);
}

/**
 * URL de page MegaStats (WHM : conserve cpsess).
 */
function ms_page_url(array $config, array $params = []): string
{
    return ms_api_url($config, $params);
}

function ms_popup_js(string $url, string $name, string $features): string
{
    $safeUrl = ms_e($url);
    $safeName = ms_e($name);
    $safeFeatures = ms_e($features);
    return "window.open('{$safeUrl}','{$safeName}','{$safeFeatures}'); return false;";
}

function ms_csrf_token(): string
{
    if (empty($_SESSION['ms_csrf_token'])) {
        $_SESSION['ms_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['ms_csrf_token'];
}

function ms_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . ms_e(ms_csrf_token()) . '">';
}

function ms_verify_csrf(array $config): bool
{
    if (empty($config['csrf_enabled'])) {
        return true;
    }

    $token = ms_post('csrf_token', '');
    $expected = $_SESSION['ms_csrf_token'] ?? '';

    return is_string($token) && is_string($expected) && hash_equals($expected, $token);
}

function ms_sanitize_whois(string $value): string
{
    return preg_replace('/[^a-z0-9-.]/', '', strtolower($value)) ?? '';
}

function ms_start_output_buffer(array $config): void
{
    if ((int) ($config['gzip'] ?? 0) === 1) {
        ini_set('zlib.output_compression_level', '1');
        ob_start('ob_gzhandler');
    }
}

function ms_security_headers(): void
{
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

function ms_render_template(string $template, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require MEGASTATS_ROOT . '/templates/' . $template . '.php';
}

function ms_load_percent(float $load): int
{
    $clamped = min(max($load, 0.0), 10.0);
    return (int) round($clamped * 10);
}

function ms_load_level(float $load): string
{
    if ($load >= 5) {
        return 'danger';
    }
    if ($load >= 1) {
        return 'warning';
    }
    return 'success';
}
