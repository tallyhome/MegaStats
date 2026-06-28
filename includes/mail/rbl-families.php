<?php

declare(strict_types=1);

function ms_mail_rbl_family_definitions(): array
{
    return [
        'spamhaus' => [
            'label' => 'Spamhaus',
            'impact' => 'critical',
            'impact_label' => 'Critique',
            'zones' => ['zen.spamhaus.org', 'pbl.spamhaus.org', 'sbl.spamhaus.org', 'xbl.spamhaus.org'],
        ],
        'uceprotect' => [
            'label' => 'UCEProtect',
            'impact' => 'critical',
            'impact_label' => 'Critique',
            'zones' => [
                'dnsbl-0.uceprotect.net',
                'dnsbl-1.uceprotect.net',
                'dnsbl-2.uceprotect.net',
                'dnsbl-3.uceprotect.net',
            ],
        ],
        'spamcop' => [
            'label' => 'Spamcop',
            'impact' => 'important',
            'impact_label' => 'Important',
            'zones' => ['bl.spamcop.net'],
        ],
        'barracuda' => [
            'label' => 'Barracuda',
            'impact' => 'critical',
            'impact_label' => 'Critique',
            'zones' => ['b.barracudacentral.org'],
        ],
        'sorbs' => [
            'label' => 'SORBS',
            'impact' => 'important',
            'impact_label' => 'Important',
            'zones' => ['dnsbl.sorbs.net', 'rhsbl.sorbs.net'],
        ],
        'cbl' => [
            'label' => 'CBL Abuseat',
            'impact' => 'critical',
            'impact_label' => 'Critique',
            'zones' => ['cbl.abuseat.org'],
        ],
        'other' => [
            'label' => 'Autres listes',
            'impact' => 'info',
            'impact_label' => 'Informatif',
            'zones' => [],
        ],
    ];
}

function ms_mail_rbl_zone_to_family(string $zone): string
{
    foreach (ms_mail_rbl_family_definitions() as $id => $def) {
        if ($id === 'other') {
            continue;
        }
        if (in_array($zone, $def['zones'], true)) {
            return $id;
        }
    }

    return 'other';
}

function ms_mail_group_rbl_by_family(array $rblResult): array
{
    $defs = ms_mail_rbl_family_definitions();
    $groups = [];

    foreach ($defs as $id => $def) {
        $groups[$id] = [
            'id' => $id,
            'label' => $def['label'],
            'impact' => $def['impact'],
            'impact_label' => $def['impact_label'],
            'listed_count' => 0,
            'total_count' => 0,
            'items' => [],
            'any_listed' => false,
        ];
    }

    foreach ($rblResult['all'] ?? [] as $item) {
        $zone = (string) ($item['zone'] ?? '');
        $familyId = ms_mail_rbl_zone_to_family($zone);
        if (!isset($groups[$familyId])) {
            $familyId = 'other';
        }
        $groups[$familyId]['items'][] = $item;
        $groups[$familyId]['total_count']++;
        if (!empty($item['listed'])) {
            $groups[$familyId]['listed_count']++;
            $groups[$familyId]['any_listed'] = true;
        }
    }

    $ordered = [];
    foreach (array_keys($defs) as $id) {
        if ($groups[$id]['total_count'] > 0) {
            usort($groups[$id]['items'], static function (array $a, array $b): int {
                return ((int) ($b['listed'] ?? false)) <=> ((int) ($a['listed'] ?? false));
            });
            $ordered[] = $groups[$id];
        }
    }

    $criticalFamilies = 0;
    foreach ($ordered as $g) {
        if ($g['any_listed'] && ($g['impact'] ?? '') === 'critical') {
            $criticalFamilies++;
        }
    }

    return [
        'families' => $ordered,
        'listed_count' => (int) ($rblResult['listed_count'] ?? 0),
        'critical_families' => $criticalFamilies,
        'total_zones' => (int) ($rblResult['total_zones'] ?? 0),
    ];
}

function ms_mail_delisting_guide(string $zone): array
{
    $guides = [
        'zen.spamhaus.org' => [
            'portal' => 'https://check.spamhaus.org/',
            'portal_label' => 'Spamhaus Blocklist Removal Center',
            'steps' => [
                'Corriger la cause (spam, FCrDNS, PTR).',
                'Vérifier le statut sur check.spamhaus.org.',
                'Si PBL : retrait automatique possible après ~7 jours si comportement OK.',
                'Si SBL/XBL : soumettre une demande via le portail Spamhaus.',
            ],
            'ticket' => "Demande de retrait Spamhaus pour l'IP {ip}.\nCause corrigée : FCrDNS/PTR vérifiés, envoi légitime.\nMerci de confirmer le retrait.",
        ],
        'pbl.spamhaus.org' => [
            'portal' => 'https://check.spamhaus.org/',
            'portal_label' => 'Spamhaus PBL',
            'steps' => [
                'Vérifier que l\'IP est une IP dynamique/residentielle ou corriger FCrDNS.',
                'Demande de retrait PBL via check.spamhaus.org si nécessaire.',
            ],
            'ticket' => "Retrait PBL Spamhaus pour {ip} — FCrDNS corrigé.",
        ],
        'sbl.spamhaus.org' => [
            'portal' => 'https://check.spamhaus.org/',
            'portal_label' => 'Spamhaus SBL',
            'steps' => ['Arrêter toute activité spam.', 'Corriger FCrDNS et authentification mail.', 'Demande via check.spamhaus.org.'],
            'ticket' => "Retrait SBL Spamhaus pour {ip}.",
        ],
        'xbl.spamhaus.org' => [
            'portal' => 'https://check.spamhaus.org/',
            'portal_label' => 'Spamhaus XBL',
            'steps' => ['Scanner le serveur (compromission botnet).', 'Corriger sécurité + FCrDNS.', 'Demande via check.spamhaus.org.'],
            'ticket' => "Retrait XBL Spamhaus pour {ip} — serveur sécurisé.",
        ],
        'dnsbl-3.uceprotect.net' => [
            'portal' => 'https://www.uceprotect.net/en/rblcheck.php',
            'portal_label' => 'UCEProtect L3',
            'steps' => [
                'Corriger FCrDNS et arrêter le spam.',
                'UCEProtect L3 peut être payant ou nécessiter un délai TTL.',
                'Vérifier sur uceprotect.net — attendre expiration TTL si applicable.',
            ],
            'ticket' => "Retrait UCEProtect L3 pour {ip}.\nFCrDNS : OK\nSpam : corrigé\nMerci de retirer l'IP.",
        ],
        'dnsbl-2.uceprotect.net' => [
            'portal' => 'https://www.uceprotect.net/en/rblcheck.php',
            'portal_label' => 'UCEProtect L2',
            'steps' => ['Corriger la cause.', 'Retrait L2 via portail UCEProtect.'],
            'ticket' => "Retrait UCEProtect L2 pour {ip}.",
        ],
        'dnsbl-1.uceprotect.net' => [
            'portal' => 'https://www.uceprotect.net/en/rblcheck.php',
            'portal_label' => 'UCEProtect L1',
            'steps' => ['Souvent lié à L2/L3 — corriger cause racine.'],
            'ticket' => "Retrait UCEProtect L1 pour {ip}.",
        ],
        'cbl.abuseat.org' => [
            'portal' => 'https://cbl.abuseat.org/lookup.cgi',
            'portal_label' => 'CBL Abuseat',
            'steps' => ['Rechercher l\'IP sur cbl.abuseat.org.', 'Corriger compromission / spam.', 'Auto-retrait après correction.'],
            'ticket' => "Retrait CBL pour {ip} — cause corrigée.",
        ],
        'bl.spamcop.net' => [
            'portal' => 'https://www.spamcop.net/bl.shtml',
            'portal_label' => 'Spamcop',
            'steps' => ['Vérifier sur spamcop.net.', 'Attendre expiration automatique (24–48 h) après correction.'],
            'ticket' => "Vérification Spamcop pour {ip}.",
        ],
        'b.barracudacentral.org' => [
            'portal' => 'https://www.barracudacentral.org/rbl/removal-request',
            'portal_label' => 'Barracuda',
            'steps' => ['Demande de retrait sur barracudacentral.org.', 'Corriger FCrDNS et réputation.'],
            'ticket' => "Retrait Barracuda pour {ip}.",
        ],
    ];

    $family = ms_mail_rbl_zone_to_family($zone);
    if (isset($guides[$zone])) {
        return $guides[$zone];
    }

    if ($family === 'spamhaus') {
        return $guides['zen.spamhaus.org'];
    }
    if (str_contains($zone, 'uceprotect')) {
        return $guides['dnsbl-3.uceprotect.net'];
    }

    return [
        'portal' => 'https://mxtoolbox.com/blacklists.aspx',
        'portal_label' => 'MXToolbox Lookup',
        'steps' => [
            'Identifier la liste sur le portail officiel.',
            'Corriger FCrDNS, PTR, SPF et arrêter le spam.',
            'Soumettre une demande de retrait si le portail le propose.',
            'Revérifier dans MegaStats après 24–48 h.',
        ],
        'ticket' => "Demande assistance délisting RBL ({zone}) pour l'IP {ip}.",
    ];
}
