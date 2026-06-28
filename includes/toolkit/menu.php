<?php

declare(strict_types=1);

function ms_toolkit_categories(): array
{
    return [
        'accounts' => ['label' => 'Comptes cPanel', 'icon' => 'bi-people'],
        'reports' => ['label' => 'Rapports & audit', 'icon' => 'bi-clipboard-data'],
        'infra' => ['label' => 'Infrastructure', 'icon' => 'bi-hdd-network'],
        'deploy' => ['label' => 'Déploiement', 'icon' => 'bi-rocket'],
        'advanced' => ['label' => 'Avancé', 'icon' => 'bi-stars'],
    ];
}

function ms_toolkit_items(): array
{
    return [
        ['id' => 'move_account', 'num' => 1, 'label' => 'Déplacer un compte', 'category' => 'accounts', 'icon' => 'bi-arrow-left-right', 'web' => false, 'cli' => true],
        ['id' => 'change_owner', 'num' => 2, 'label' => 'Changer le propriétaire', 'category' => 'accounts', 'icon' => 'bi-person-gear', 'web' => false, 'cli' => true],
        ['id' => 'change_ip', 'num' => 3, 'label' => 'Changer l\'IP', 'category' => 'accounts', 'icon' => 'bi-globe2', 'web' => false, 'cli' => true],
        ['id' => 'move_reseller', 'num' => 4, 'label' => 'Déplacer tous les comptes d\'un revendeur', 'category' => 'accounts', 'icon' => 'bi-diagram-3', 'web' => false, 'cli' => true],
        ['id' => 'check_inconsistencies', 'num' => 5, 'label' => 'Vérifier les incohérences', 'category' => 'accounts', 'icon' => 'bi-search', 'web' => true, 'cli' => true, 'script' => 'check-inconsistencies.sh'],
        ['id' => 'create_account', 'num' => 6, 'label' => 'Créer un nouveau compte', 'category' => 'accounts', 'icon' => 'bi-person-plus', 'web' => false, 'cli' => true],
        ['id' => 'fix_permissions', 'num' => 9, 'label' => 'Corriger les permissions', 'category' => 'accounts', 'icon' => 'bi-wrench', 'web' => false, 'cli' => true],
        ['id' => 'server_report', 'num' => 10, 'label' => 'Rapport serveur', 'category' => 'reports', 'icon' => 'bi-file-earmark-text', 'web' => true, 'cli' => true, 'script' => 'server-report.sh'],
        ['id' => 'full_audit', 'num' => 11, 'label' => 'Audit complet du serveur', 'category' => 'reports', 'icon' => 'bi-clipboard-check', 'web' => true, 'cli' => true, 'script' => 'full-audit.sh'],
        ['id' => 'statistics', 'num' => 12, 'label' => 'Statistiques', 'category' => 'reports', 'icon' => 'bi-graph-up', 'web' => true, 'cli' => true, 'script' => 'statistics.sh'],
        ['id' => 'backups', 'num' => 13, 'label' => 'Sauvegardes', 'category' => 'infra', 'icon' => 'bi-archive', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'ssl', 'num' => 14, 'label' => 'SSL', 'category' => 'infra', 'icon' => 'bi-shield-lock', 'web' => true, 'cli' => true, 'script' => 'ssl-status.sh'],
        ['id' => 'exim', 'num' => 15, 'label' => 'Exim', 'category' => 'infra', 'icon' => 'bi-envelope', 'web' => true, 'cli' => true, 'script' => 'exim-status.sh'],
        ['id' => 'dns', 'num' => 16, 'label' => 'DNS', 'category' => 'infra', 'icon' => 'bi-signpost-split', 'web' => true, 'cli' => true, 'script' => 'dns-status.sh'],
        ['id' => 'disk_space', 'num' => 17, 'label' => 'Espace disque', 'category' => 'infra', 'icon' => 'bi-device-hdd', 'web' => true, 'cli' => true, 'script' => 'disk-space.sh'],
        ['id' => 'git_tools', 'num' => 18, 'label' => 'Git', 'category' => 'deploy', 'icon' => 'bi-git', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'docker', 'num' => 19, 'label' => 'Docker', 'category' => 'deploy', 'icon' => 'bi-box', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'nodejs', 'num' => 20, 'label' => 'NodeJS', 'category' => 'deploy', 'icon' => 'bi-node-plus', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'laravel', 'num' => 7, 'label' => 'Installer Laravel', 'category' => 'deploy', 'icon' => 'bi-code-slash', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'wordpress', 'num' => 8, 'label' => 'Installer WordPress', 'category' => 'deploy', 'icon' => 'bi-wordpress', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'php_versions', 'num' => 21, 'label' => 'Versions PHP', 'category' => 'deploy', 'icon' => 'bi-filetype-php', 'web' => true, 'cli' => true, 'script' => 'php-versions.sh'],
        ['id' => 'firewall', 'num' => 22, 'label' => 'Firewall', 'category' => 'advanced', 'icon' => 'bi-fire', 'web' => false, 'cli' => true, 'soon' => true],
        ['id' => 'ai_errors', 'num' => 23, 'label' => 'IA — analyser les erreurs', 'category' => 'advanced', 'icon' => 'bi-robot', 'web' => false, 'cli' => true, 'soon' => true],
    ];
}

function ms_toolkit_find(string $id): ?array
{
    foreach (ms_toolkit_items() as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }

    return null;
}

function ms_toolkit_items_by_category(): array
{
    $grouped = [];
    foreach (ms_toolkit_categories() as $key => $meta) {
        $grouped[$key] = ['meta' => $meta, 'items' => []];
    }
    foreach (ms_toolkit_items() as $item) {
        $cat = $item['category'] ?? 'advanced';
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = ['meta' => ['label' => $cat, 'icon' => 'bi-grid'], 'items' => []];
        }
        $grouped[$cat]['items'][] = $item;
    }

    return $grouped;
}
