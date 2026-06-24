#!/bin/bash
#
# MegaStats — WHM plugin uninstaller
#
set -euo pipefail

INSTALL_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/megastats"
DATA_DIR="/var/cpanel/megastats"
APPCONF="/var/cpanel/apps/megastats.conf"
CRON_FILE="/etc/cron.d/megastats"

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez ce script en root." >&2
    exit 1
fi

read -r -p "Supprimer aussi les données ($DATA_DIR) ? [y/N] " PURGE
PURGE="${PURGE:-N}"

if [[ -x /usr/local/cpanel/bin/unregister_appconfig ]]; then
    /usr/local/cpanel/bin/unregister_appconfig megastats 2>/dev/null || true
elif [[ -x /usr/local/cpanel/scripts/uninstall_appconfig ]]; then
    /usr/local/cpanel/scripts/uninstall_appconfig megastats 2>/dev/null || true
fi

rm -f "$APPCONF" "$CRON_FILE"
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/addon_megastats.cgi
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/megastats.cgi
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/megastats/index.cgi
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/megastats_addon.cgi
rm -f /usr/local/cpanel/whostmgr/docroot/themes/x/dynamicui/dynamicui_megastats.conf
rm -f /usr/local/cpanel/whostmgr/docroot/themes/x/dynamicui_megastats.conf
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/megastats_menu.cgi
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/megastats_test.cgi
rm -f /usr/local/cpanel/whostmgr/addonfeatures/megastats /usr/local/cpanel/whostmgr/addonfeatures/megastats.acl
rm -rf "$INSTALL_DIR"

if [[ "$PURGE" =~ ^[Yy]$ ]]; then
    rm -rf "$DATA_DIR"
    echo "Données supprimées."
else
    echo "Données conservées dans $DATA_DIR"
fi

if [[ -x /usr/local/cpanel/bin/rebuild_sprites ]]; then
    /usr/local/cpanel/bin/rebuild_sprites 2>/dev/null || true
fi

echo "MegaStats WHM désinstallé."
