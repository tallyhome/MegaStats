<?php

declare(strict_types=1);

function ms_mail_auto_fix_plan(string $ip, array $config, string $domain): array
{
    $prefix = (string) ($config['mail_hostname_prefix'] ?? 'mail-r');
    $hostnameDomain = trim((string) ($config['mail_hostname_domain'] ?? ''));
    if ($hostnameDomain === '') {
        $hostnameDomain = $domain;
    }

    $fcrdns = ms_mail_check_fcrdns($ip);
    $host = (string) ($fcrdns['hostname'] ?? $prefix . '1.' . $hostnameDomain);
    if (($fcrdns['ok'] ?? false) === false && empty($fcrdns['hostname'])) {
        $host = $prefix . preg_replace('/\D/', '', strrchr($ip, '.') ?: '1') . '.' . $hostnameDomain;
    }

    $actions = [];
    if (empty($fcrdns['a_records']) || !in_array($ip, $fcrdns['a_records'], true)) {
        $actions[] = [
            'type' => 'A',
            'name' => $host,
            'value' => $ip,
            'detail' => "Créer A $host → $ip",
        ];
    }
    $spf = ms_mail_check_spf($domain);
    $spfIp = ms_mail_check_spf_includes_ip($domain, $ip);
    if (!($spfIp['ok'] ?? false)) {
        $current = ($spf['ok'] ?? false) ? (string) ($spf['detail'] ?? '') : 'v=spf1';
        $newSpf = rtrim($current, ' ') . ' ip4:' . $ip;
        if (!str_contains($current, 'ip4:' . $ip)) {
            $actions[] = ['type' => 'SPF', 'name' => $domain, 'value' => $newSpf, 'detail' => 'Ajouter ip4:' . $ip . ' au SPF'];
        }
    }
    if (($config['mail_auto_fix_dkim'] ?? true) && !ms_mail_check_dkim($domain, $config['mail_dkim_selectors'] ?? ['default'])['ok']) {
        $actions[] = ['type' => 'DKIM', 'name' => $domain, 'value' => '', 'detail' => 'Activer DKIM cPanel (/usr/local/cpanel/bin/dkim_keys_install)'];
    }
    if (!ms_mail_check_dmarc($domain)['ok']) {
        $actions[] = [
            'type' => 'DMARC',
            'name' => '_dmarc.' . $domain,
            'value' => 'v=DMARC1; p=none; rua=mailto:dmarc@' . $domain,
            'detail' => 'Publier enregistrement DMARC par défaut',
        ];
    }

    return [
        'ip' => $ip,
        'domain' => $domain,
        'hostname' => $host,
        'actions' => $actions,
        'ptr_note' => 'PTR : configurez manuellement ' . $ip . ' → ' . $host . ' (provider/WHM)',
    ];
}

function ms_mail_apply_auto_fix(array $config, string $ip): array
{
    if ((function_exists('posix_geteuid') ? posix_geteuid() : 0) !== 0) {
        return ['ok' => false, 'message' => 'Root requis pour corriger automatiquement.'];
    }

    $domain = ms_mail_detect_domains($config)[0] ?? 'localhost';
    $plan = ms_mail_auto_fix_plan($ip, $config, $domain);
    $applied = [];
    $errors = [];

    foreach ($plan['actions'] as $action) {
        if ($action['type'] === 'A') {
            $zoneUser = ms_mail_domain_owner($domain);
            if ($zoneUser === null) {
                $errors[] = 'Propriétaire zone introuvable pour ' . $domain;
                continue;
            }
            $hostShort = str_replace('.' . $domain, '', (string) $action['name']);
            if ($hostShort === $action['name']) {
                $hostShort = (string) $action['name'];
            }
            $cmd = 'uapi --user=' . escapeshellarg($zoneUser)
                . ' DNS add_zone_record domain=' . escapeshellarg($domain)
                . ' name=' . escapeshellarg($hostShort)
                . ' type=A address=' . escapeshellarg((string) $action['value']) . ' 2>&1';
            $out = ms_shell($cmd);
            $applied[] = $action['detail'] . ' → ' . trim($out);
        } elseif ($action['type'] === 'SPF') {
            $zoneUser = ms_mail_domain_owner($domain);
            if ($zoneUser === null) {
                $errors[] = 'SPF : zone owner inconnu';
                continue;
            }
            $cmd = 'uapi --user=' . escapeshellarg($zoneUser)
                . ' DNS add_zone_record domain=' . escapeshellarg($domain)
                . ' name=' . escapeshellarg($domain) . ' type=TXT txtdata=' . escapeshellarg((string) $action['value']) . ' 2>&1';
            $applied[] = $action['detail'] . ' (vérifiez doublons TXT manuellement)';
        } elseif ($action['type'] === 'DMARC') {
            $zoneUser = ms_mail_domain_owner($domain);
            if ($zoneUser === null) {
                continue;
            }
            $cmd = 'uapi --user=' . escapeshellarg($zoneUser)
                . ' DNS add_zone_record domain=' . escapeshellarg($domain)
                . ' name=_dmarc type=TXT txtdata=' . escapeshellarg((string) $action['value']) . ' 2>&1';
            $applied[] = $action['detail'];
        } elseif ($action['type'] === 'DKIM') {
            ms_shell('/usr/local/cpanel/bin/dkim_keys_install ' . escapeshellarg($domain) . ' 2>&1');
            $applied[] = $action['detail'];
        }
    }

    ms_mail_run_scan($config);

    return [
        'ok' => $errors === [],
        'message' => $applied !== [] ? implode("\n", $applied) : 'Aucune action applicable.',
        'errors' => $errors,
        'plan' => $plan,
    ];
}

/** @return list<string> */
function ms_mail_auto_fix_action_labels(array $plan): array
{
    $labels = [];
    foreach ($plan['actions'] ?? [] as $action) {
        $labels[] = (string) ($action['detail'] ?? $action['type'] ?? 'Correction');
    }

    return $labels;
}

function ms_mail_auto_fix_has_actions(array $plan): bool
{
    return ($plan['actions'] ?? []) !== [];
}

function ms_mail_exim_needs_mailips_rebuild(array $exim): bool
{
    if (!($exim['mailips']['ok'] ?? false)) {
        return true;
    }
    if (!($exim['mailhelo']['ok'] ?? false)) {
        return true;
    }
    if (!($exim['send_from_account_ip']['ok'] ?? false)) {
        return true;
    }

    return !($exim['consistent'] ?? true);
}

function ms_mail_domain_owner(string $domain): ?string
{
    if (!is_file('/etc/userdomains')) {
        return null;
    }
    foreach (file('/etc/userdomains', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $parts = preg_split('/\s*:\s*/', trim($line), 2);
        if (count($parts) === 2 && strtolower($parts[0]) === strtolower($domain)) {
            return trim($parts[1]);
        }
    }

    return null;
}

function ms_mail_account_uses_dedicated_ip(string $user, string $ip): bool
{
    $userIp = ms_mail_user_ip($user);
    if ($userIp === null) {
        return false;
    }

    return $userIp === $ip;
}

function ms_mail_send_from_account_ip_enabled(): bool
{
    if (!is_file('/var/cpanel/cpanel.config')) {
        return false;
    }

    return (bool) preg_match('/^sendmailfromaccountip=1/m', (string) file_get_contents('/var/cpanel/cpanel.config'));
}
