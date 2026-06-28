<?php

declare(strict_types=1);

function ms_toolkit_build_page_view(array $config): array
{
    $actionId = trim((string) ms_get('action', ''));
    $result = null;
    if ($actionId !== '') {
        $result = ms_toolkit_run_action($actionId);
    }

    $cliPath = trim((string) ($config['toolkit_cli_path'] ?? ''));
    if ($cliPath === '') {
        $cliPath = is_file('/opt/megastats/toolkit/server-toolkit.sh')
            ? '/opt/megastats/toolkit/server-toolkit.sh'
            : MEGASTATS_ROOT . '/toolkit/server-toolkit.sh';
    }

    return [
        'page_title' => 'Server Toolkit · MegaStats',
        'categories' => ms_toolkit_items_by_category(),
        'cli_path' => $cliPath,
        'action_id' => $actionId !== '' ? $actionId : null,
        'action_result' => $result,
        'scriptname' => $config['scriptname'],
        'dashboard_url' => ms_page_url($config, []),
        'toolkit_url' => ms_page_url($config, ['page' => 'toolkit']),
        'assets_base' => $config['assets_base'],
        'version' => $config['version'],
        'csrf_field' => ms_csrf_field(),
        'whm_embedded' => ms_is_whm_deployment($config),
        'can_use' => ms_toolkit_can_use($config),
        'ms_link' => static fn(array $params = []): string => ms_page_url($config, array_merge(['page' => 'toolkit'], $params)),
    ];
}

function ms_handle_toolkit_page(array $config): bool
{
    if ((string) ms_get('page', '') !== 'toolkit') {
        return false;
    }

    if (defined('MEGASTATS_WHM') && MEGASTATS_WHM) {
        return false;
    }

    if (!($config['toolkit_enabled'] ?? false)) {
        http_response_code(404);
        echo 'Server Toolkit désactivé.';
        return true;
    }

    if (!ms_toolkit_can_use($config)) {
        http_response_code(403);
        echo 'Server Toolkit réservé à WHM root.';
        return true;
    }

    ms_start_output_buffer($config);
    $view = ms_toolkit_build_page_view($config);
    $view['auth_mode'] = $config['auth_mode'] ?? 'password';
    $view['deployment'] = $config['deployment'] ?? 'standalone';
    ms_render_template('toolkit/overview', $view);

    return true;
}

function ms_render_toolkit_page_whm(array $config): void
{
    if (!($config['toolkit_enabled'] ?? false) || !ms_toolkit_can_use($config)) {
        ms_whm_require_access($config);
        http_response_code(403);
        echo 'Server Toolkit non disponible.';
        return;
    }

    ms_start_output_buffer($config);
    $view = ms_toolkit_build_page_view($config);
    $view['whm_embedded'] = true;
    $view['auth_mode'] = 'whm';
    $view['deployment'] = 'whm';

    $assetsBase = ms_e($config['assets_base']);
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">' . "\n";
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">' . "\n";
    echo '<link href="' . $assetsBase . '/css/app.css" rel="stylesheet">' . "\n";
    echo '<script>document.documentElement.setAttribute("data-bs-theme", localStorage.getItem("megastats-theme") === "light" ? "light" : "dark");</script>' . "\n";
    echo '<div class="container-fluid py-3 ms-whm-wrap" data-bs-theme="dark">' . "\n";
    ms_render_template('toolkit/overview', $view);
    echo "</div>\n";
}
