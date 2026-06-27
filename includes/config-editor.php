<?php

declare(strict_types=1);

function ms_config_dir(): string
{
    return MEGASTATS_ROOT . '/config';
}

function ms_config_definitions(array $config): array
{
    $defs = [
        'app' => [
            'file' => 'app.php',
            'label' => 'Application',
            'description' => 'Rafraîchissement, historique, cron et cache shell.',
            'fields' => [
                'name' => ['type' => 'readonly', 'label' => 'Nom'],
                'version' => ['type' => 'readonly', 'label' => 'Version'],
                'cron_token' => ['type' => 'string', 'label' => 'Jeton cron', 'hint' => 'Secret pour cron.php / cron-mail.php'],
            ],
        ],
        'monitoring' => [
            'file' => 'monitoring.php',
            'label' => 'Monitoring',
            'description' => 'MySQL, processus surveillés et commandes shell.',
            'fields' => [
                'processes' => ['type' => 'string', 'label' => 'Processus (liste séparée par espaces)'],
                'allowed_cmds' => ['type' => 'lines', 'label' => 'Commandes autorisées (popups)'],
            ],
        ],
        'security' => [
            'file' => 'security.php',
            'label' => 'Sécurité',
            'description' => 'Authentification standalone (ignoré en mode WHM).',
            'fields' => [
                'username' => ['type' => 'readonly', 'label' => 'Identifiant'],
                'password_hash' => ['type' => 'password_hash', 'label' => 'Mot de passe', 'hint' => 'Laisser vide pour conserver le hash actuel'],
                'new_password' => ['type' => 'new_password', 'label' => 'Nouveau mot de passe'],
                'ip_whitelist' => ['type' => 'lines', 'label' => 'IP autorisées (vide = toutes)'],
            ],
        ],
        'mail' => [
            'file' => 'mail.php',
            'label' => 'Mail & délivrabilité',
            'description' => 'Scans RBL, rapports et tests inbox.',
            'fields' => [
                'mail_report_email' => [
                    'type' => 'email',
                    'label' => 'E-mail rapport quotidien',
                    'hint' => 'Reçoit le rapport délivrabilité chaque jour (heure ci-dessous). Laissez vide pour désactiver.',
                ],
                'mail_alert_email' => [
                    'type' => 'email',
                    'label' => 'E-mail alertes RBL',
                    'hint' => 'Alerte immédiate si une IP est listée sur une blacklist. Vide = même adresse que le rapport.',
                ],
                'mail_report_hour' => ['type' => 'int', 'label' => 'Heure du rapport (0–23)', 'hint' => 'Par défaut 7h (cron-mail.php)'],
                'mail_scan_hour' => ['type' => 'int', 'label' => 'Heure du scan (0–23)', 'hint' => 'Par défaut 6h (cron-mail.php)'],
                'mail_helo_name' => ['type' => 'string', 'label' => 'Nom HELO SMTP', 'hint' => 'Vide = hostname du serveur'],
                'mail_domains' => ['type' => 'lines', 'label' => 'Domaines (un par ligne, vide = auto)'],
                'mail_sending_ips' => ['type' => 'lines', 'label' => 'IP d\'envoi (vide = auto)'],
                'mail_dkim_selectors' => ['type' => 'lines', 'label' => 'Sélecteurs DKIM'],
                'mail_test_inboxes' => ['type' => 'map', 'label' => 'Boîtes test inbox'],
                'mail_test_mx_hosts' => ['type' => 'map', 'label' => 'Hôtes MX de test'],
                'update_script' => ['type' => 'readonly', 'label' => 'Script mise à jour'],
                'update_git_repo' => ['type' => 'readonly', 'label' => 'Dépôt GitHub'],
            ],
        ],
        'alerts' => [
            'file' => 'alerts.php',
            'label' => 'Alertes',
            'description' => 'Seuils CPU, RAM, charge, disque et réseau.',
            'fields' => [],
        ],
        'distribution' => [
            'file' => 'distribution.php',
            'label' => 'Distribution',
            'description' => 'Liens GitHub, don et compatibilité cPanel.',
            'fields' => [],
        ],
    ];

    if (ms_is_whm_deployment($config)) {
        $defs['app_whm'] = [
            'file' => 'app.whm.php',
            'label' => 'WHM',
            'description' => 'Chemins et options spécifiques au déploiement WHM.',
            'fields' => [
                'whm_acls' => ['type' => 'lines', 'label' => 'Utilisateurs WHM autorisés'],
            ],
        ];
    }

    return $defs;
}

function ms_config_file_path(string $file): string
{
    $file = basename($file);
    if (!preg_match('/^[a-z0-9._-]+\.php$/i', $file)) {
        throw new InvalidArgumentException('Invalid config file');
    }

    return ms_config_dir() . '/' . $file;
}

function ms_config_load(string $fileId, array $definitions): array
{
    if (!isset($definitions[$fileId])) {
        throw new InvalidArgumentException('Unknown config');
    }

    $path = ms_config_file_path($definitions[$fileId]['file']);

    if (!is_file($path)) {
        return [];
    }

    $data = require $path;

    return is_array($data) ? $data : [];
}

function ms_config_infer_type(mixed $value): string
{
    if (is_bool($value)) {
        return 'bool';
    }
    if (is_int($value)) {
        return 'int';
    }
    if (is_float($value)) {
        return 'float';
    }
    if (is_array($value)) {
        if ($value === [] || array_is_list($value)) {
            return 'lines';
        }

        return 'map';
    }

    return 'string';
}

function ms_config_field_meta(string $key, mixed $value, array $overrides): array
{
    if (isset($overrides[$key])) {
        return array_merge(['key' => $key, 'value' => $value], $overrides[$key]);
    }

    return [
        'key' => $key,
        'value' => $value,
        'type' => ms_config_infer_type($value),
        'label' => ucwords(str_replace('_', ' ', $key)),
    ];
}

function ms_config_fields_for_file(string $fileId, array $definitions): array
{
    $def = $definitions[$fileId];
    $data = ms_config_load($fileId, $definitions);
    $overrides = $def['fields'] ?? [];
    $fields = [];

    foreach ($data as $key => $value) {
        if ($key === 'new_password') {
            continue;
        }
        if ($key === 'password_hash' && isset($overrides['password_hash'])) {
            $fields[] = ms_config_field_meta($key, $value, $overrides);
            $fields[] = [
                'key' => 'new_password',
                'value' => '',
                'type' => 'new_password',
                'label' => $overrides['new_password']['label'] ?? 'Nouveau mot de passe',
                'hint' => $overrides['new_password']['hint'] ?? '',
            ];
            continue;
        }
        $fields[] = ms_config_field_meta($key, $value, $overrides);
    }

    return $fields;
}

function ms_config_parse_value(string $type, mixed $raw, mixed $current): mixed
{
    return match ($type) {
        'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
        'int' => (int) $raw,
        'float' => (float) str_replace(',', '.', (string) $raw),
        'lines' => ms_config_parse_lines((string) $raw),
        'map' => is_array($raw) ? ms_config_parse_map_array($raw) : ms_config_parse_map_text((string) $raw),
        'readonly', 'password_hash' => $current,
        'email' => trim((string) $raw),
        'new_password' => (string) $raw,
        default => trim((string) $raw),
    };
}

function ms_config_parse_lines(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }

    return $out;
}

function ms_config_parse_map_text(string $text): array
{
    $map = [];
    foreach (ms_config_parse_lines($text) as $line) {
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $map[trim($k)] = trim($v);
    }

    return $map;
}

function ms_config_parse_map_array(array $raw): array
{
    $map = [];
    foreach ($raw as $k => $v) {
        $k = trim((string) $k);
        if ($k === '') {
            continue;
        }
        $map[$k] = trim((string) $v);
    }

    return $map;
}

function ms_config_format_lines(array $value): string
{
    return implode("\n", array_map('strval', $value));
}

function ms_config_format_map(array $value): string
{
    $lines = [];
    foreach ($value as $k => $v) {
        $lines[] = $k . '=' . $v;
    }

    return implode("\n", $lines);
}

function ms_config_build_from_post(string $fileId, array $definitions): array
{
    $current = ms_config_load($fileId, $definitions);
    $fields = ms_config_fields_for_file($fileId, $definitions);
    $posted = ms_post('cfg', []);
    if (!is_array($posted)) {
        $posted = [];
    }

    $result = $current;

    foreach ($fields as $field) {
        $key = $field['key'];
        $type = $field['type'];

        if ($type === 'new_password') {
            continue;
        }
        if ($type === 'readonly') {
            continue;
        }

        if ($type === 'bool') {
            $result[$key] = isset($posted[$key]) && (string) $posted[$key] === '1';
            continue;
        }

        if (!array_key_exists($key, $posted) && $type !== 'password_hash') {
            continue;
        }

        $result[$key] = ms_config_parse_value($type, $posted[$key] ?? '', $current[$key] ?? null);
    }

    $newPassword = trim((string) ($posted['new_password'] ?? ''));
    if ($newPassword !== '' && $fileId === 'security') {
        $result['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    return $result;
}

function ms_config_export_php(array $data): string
{
    $lines = ["<?php\n", "\n", "declare(strict_types=1);\n", "\n", "return [\n"];
    $lines = array_merge($lines, ms_config_export_entries($data, 1));
    $lines[] = "];\n";

    return implode('', $lines);
}

function ms_config_export_entries(array $data, int $depth): array
{
    $pad = str_repeat('    ', $depth);
    $lines = [];

    foreach ($data as $key => $value) {
        $exportedKey = is_int($key) ? (string) $key : "'" . addcslashes((string) $key, "'\\") . "'";

        if (is_array($value)) {
            if ($value === []) {
                $lines[] = $pad . $exportedKey . " => [],\n";
                continue;
            }
            $lines[] = $pad . $exportedKey . " => [\n";
            $lines = array_merge($lines, ms_config_export_entries($value, $depth + 1));
            $lines[] = $pad . "],\n";
            continue;
        }

        $lines[] = $pad . $exportedKey . ' => ' . ms_config_export_scalar($value) . ",\n";
    }

    return $lines;
}

function ms_config_export_scalar(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . addcslashes((string) $value, "'\\") . "'";
}

function ms_config_save(string $fileId, array $definitions, array $data): bool
{
    if (!isset($definitions[$fileId])) {
        return false;
    }

    $path = ms_config_file_path($definitions[$fileId]['file']);
    $dir = dirname($path);

    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    if (is_file($path) && !is_writable($path)) {
        return false;
    }

    $content = ms_config_export_php($data);
    $tmp = $path . '.tmp.' . getmypid();

    if (file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }

    if (!rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

function ms_config_is_writable(array $definitions): bool
{
    $dir = ms_config_dir();
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    foreach ($definitions as $def) {
        $path = ms_config_file_path($def['file']);
        if (is_file($path) && !is_writable($path)) {
            return false;
        }
    }

    return true;
}

function ms_config_build_page_view(array $config): array
{
    $definitions = ms_config_definitions($config);
    $fileId = (string) ms_get('file', 'app');
    if (!isset($definitions[$fileId])) {
        $fileId = array_key_first($definitions) ?: 'app';
    }

    return [
        'page_title' => 'Configuration · MegaStats',
        'definitions' => $definitions,
        'active_file' => $fileId,
        'fields' => ms_config_fields_for_file($fileId, $definitions),
        'config_writable' => ms_config_is_writable($definitions),
        'scriptname' => $config['scriptname'],
        'dashboard_url' => ms_page_url($config, []),
        'config_url' => ms_page_url($config, ['page' => 'config']),
        'mail_url' => ms_page_url($config, ['page' => 'mail']),
        'ms_link' => static fn(array $params = []): string => ms_page_url($config, $params),
        'assets_base' => $config['assets_base'],
        'version' => $config['version'],
        'csrf_field' => ms_csrf_field(),
        'whm_embedded' => ms_is_whm_deployment($config),
        'config_flash' => match ((string) ms_get('saved', '')) {
            'ok' => 'Configuration enregistrée.',
            'fail' => 'Impossible d\'enregistrer (permissions ou erreur d\'écriture).',
            'csrf' => 'Jeton de sécurité invalide.',
            default => '',
        },
    ];
}

function ms_handle_config_page(array $config): bool
{
    if ((string) ms_get('page', '') !== 'config') {
        return false;
    }

    if (defined('MEGASTATS_WHM') && MEGASTATS_WHM) {
        return false;
    }

    $definitions = ms_config_definitions($config);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ms_post('config_action', '') === 'save') {
        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            header('Location: ' . ms_page_url($config, ['page' => 'config', 'saved' => 'csrf']));
            exit;
        }

        $fileId = (string) ms_post('file', 'app');
        $data = ms_config_build_from_post($fileId, $definitions);
        $ok = ms_config_save($fileId, $definitions, $data);
        if ($ok) {
            ms_log($config, 'activity', 'Config saved: ' . $fileId);
        }

        header('Location: ' . ms_page_url($config, [
            'page' => 'config',
            'file' => $fileId,
            'saved' => $ok ? 'ok' : 'fail',
        ]));
        exit;
    }

    ms_start_output_buffer($config);
    $view = ms_config_build_page_view($config);
    $view['auth_mode'] = $config['auth_mode'] ?? 'password';
    $view['deployment'] = $config['deployment'] ?? 'standalone';

    ms_render_template('config/editor', $view);

    return true;
}

function ms_render_config_page_whm(array $config): void
{
    $definitions = ms_config_definitions($config);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ms_post('config_action', '') === 'save') {
        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            header('Location: ' . ms_page_url($config, ['page' => 'config', 'saved' => 'csrf']));
            exit;
        }

        $fileId = (string) ms_post('file', 'app');
        $data = ms_config_build_from_post($fileId, $definitions);
        $ok = ms_config_save($fileId, $definitions, $data);
        if ($ok) {
            ms_log($config, 'activity', 'Config saved: ' . $fileId);
        }

        header('Location: ' . ms_page_url($config, [
            'page' => 'config',
            'file' => $fileId,
            'saved' => $ok ? 'ok' : 'fail',
        ]));
        exit;
    }

    ms_start_output_buffer($config);
    $view = ms_config_build_page_view($config);
    $view['whm_embedded'] = true;
    $view['auth_mode'] = 'whm';
    $view['deployment'] = 'whm';

    $assetsBase = ms_e($config['assets_base']);
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">' . "\n";
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">' . "\n";
    echo '<link href="' . $assetsBase . '/css/app.css" rel="stylesheet">' . "\n";
    echo '<script>document.documentElement.setAttribute("data-bs-theme", localStorage.getItem("megastats-theme") === "light" ? "light" : "dark");</script>' . "\n";
    echo '<div class="container-fluid py-3 ms-whm-wrap" data-bs-theme="dark">' . "\n";
    ms_render_template('config/editor', $view);
    echo "</div>\n";
}
