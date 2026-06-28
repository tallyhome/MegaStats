<?php

declare(strict_types=1);

function ms_toolkit_actions_dir(): string
{
    return MEGASTATS_ROOT . '/toolkit/actions';
}

function ms_toolkit_can_use(array $config): bool
{
    if (!($config['toolkit_enabled'] ?? false)) {
        return false;
    }

    if (!empty($config['toolkit_whm_only'])) {
        return defined('MEGASTATS_WHM') && MEGASTATS_WHM && ms_update_can_run($config);
    }

    return true;
}

function ms_toolkit_run_action(string $actionId): array
{
    $item = ms_toolkit_find($actionId);
    if ($item === null) {
        return ['ok' => false, 'output' => 'Action inconnue.', 'exit_code' => 1];
    }

    if (!empty($item['soon'])) {
        return ['ok' => false, 'output' => 'Fonction disponible prochainement. Utilisez le menu SSH en attendant.', 'exit_code' => 1];
    }

    if (empty($item['web'])) {
        return [
            'ok' => false,
            'output' => "Cette action nécessite le menu interactif SSH :\n  /opt/megastats/toolkit/server-toolkit.sh\nPuis choisissez l'option " . ($item['num'] ?? '?') . '.',
            'exit_code' => 1,
        ];
    }

    $script = (string) ($item['script'] ?? '');
    if ($script === '' || !preg_match('/^[a-z0-9-]+\.sh$/', $script)) {
        return ['ok' => false, 'output' => 'Script non configuré.', 'exit_code' => 1];
    }

    $path = ms_toolkit_actions_dir() . '/' . $script;
    if (!is_file($path)) {
        return ['ok' => false, 'output' => 'Script introuvable : ' . $path, 'exit_code' => 1];
    }

    if (!is_executable($path)) {
        @chmod($path, 0755);
    }

    $cmd = 'bash ' . escapeshellarg($path) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);

    return [
        'ok' => $code === 0,
        'output' => implode("\n", $output),
        'exit_code' => $code,
        'label' => $item['label'],
    ];
}
