#!/bin/bash
# Désactive Interface Analytics cPanel/WebPros (bandeau « Consent and Privacy »).
# MegaStats n'a aucun lien avec ce bandeau.
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez en root." >&2
    exit 1
fi

echo "==> Désactivation analytics cPanel/WebPros"
echo "    (bandeau « Consent and Privacy » dans WHM — pas MegaStats)"
echo

if command -v whmapi1 >/dev/null 2>&1; then
    echo "-- participate_in_analytics enabled=0"
    whmapi1 participate_in_analytics enabled=0 || true
fi

if [[ -x /scripts/uninstall_cpanel_analytics ]]; then
    echo "-- /scripts/uninstall_cpanel_analytics"
    /scripts/uninstall_cpanel_analytics
else
    echo "WARN : /scripts/uninstall_cpanel_analytics absent sur ce build"
fi

# Enregistrer un refus côté compte root (sinon le slideout revient)
if command -v uapi >/dev/null 2>&1; then
    echo "-- uapi root : refus tracking compte"
    uapi --user=root Personalization set analytics_opt_in=0 2>/dev/null || true
    uapi --user=root Personalization set allow_tracking=0 2>/dev/null || true
fi

if command -v whmapi1 >/dev/null 2>&1; then
    whmapi1 personalization_set store=analytics personalization='{"analytics_opt_in":"0"}' 2>/dev/null || true
fi

echo "-- redémarrage cpsrvd"
/scripts/restartsrv_cpsrvd --wait 2>/dev/null || true

echo
echo "OK. Déconnectez-vous de WHM puis reconnectez-vous."
echo "Note : une mise à jour cPanel peut réinstaller cpanel-analytics — relancez ce script si besoin."
