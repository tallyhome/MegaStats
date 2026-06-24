#!/bin/bash
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
CGI_ROOT="/usr/local/cpanel/whostmgr/docroot/cgi"
INSTALL_DIR="$CGI_ROOT/megastats"
ICON="/usr/local/cpanel/whostmgr/docroot/addon_plugins/megastats.png"
DYNAMICUI="/usr/local/cpanel/whostmgr/docroot/themes/x/dynamicui/dynamicui_megastats.conf"

echo "=== MegaStats WHM diagnostic ==="
echo

echo "-- Enregistrement AppConfig --"
if [[ -x /usr/local/cpanel/bin/is_registered_with_appconfig ]]; then
    echo -n "is_registered_with_appconfig whostmgr megastats = "
    /usr/local/cpanel/bin/is_registered_with_appconfig whostmgr megastats
else
    echo "is_registered_with_appconfig non disponible"
fi
echo

echo "-- show_appconfig (extrait megastats) --"
if [[ -x /usr/local/cpanel/bin/show_appconfig ]]; then
    /usr/local/cpanel/bin/show_appconfig 2>/dev/null | grep -A12 megastats || echo "(megastats absent du cache AppConfig)"
else
    echo "show_appconfig non disponible"
fi
echo

echo "-- addon_megastats.cgi (menu Plugins WHMADDON, Perl) --"
ls -la "$CGI_ROOT/addon_megastats.cgi" 2>/dev/null || echo "MANQUANT: $CGI_ROOT/addon_megastats.cgi"
head -4 "$CGI_ROOT/addon_megastats.cgi" 2>/dev/null || true
if grep -q '^#WHMADDON:megastats:' "$CGI_ROOT/addon_megastats.cgi" 2>/dev/null; then
    echo "OK : WHMADDON dans addon_megastats.cgi (convention addon_*.cgi)"
else
    echo "WARN : WHMADDON absent — ./whm/write-entry.sh"
fi
if [[ -f "$CGI_ROOT/megastats.cgi" ]]; then
    echo "WARN : megastats.cgi obsolète présent (non scanné par cPanel) — ./whm/fix-menu.sh"
fi
echo

echo "-- megastats/index.cgi (app PHP AppConfig) --"
ls -la "$CGI_ROOT/megastats/index.cgi" 2>/dev/null || echo "MANQUANT: $CGI_ROOT/megastats/index.cgi"
head -2 "$CGI_ROOT/megastats/index.cgi" 2>/dev/null || true
echo

if [[ -f /var/cpanel/pluginscache.yaml ]] && grep -qi megastats /var/cpanel/pluginscache.yaml 2>/dev/null; then
    echo "OK : megastats present dans pluginscache.yaml (menu Plugins)"
    grep -i megastats /var/cpanel/pluginscache.yaml 2>/dev/null || true
else
    echo "WARN : megastats absent de pluginscache.yaml — vérifier addon_megastats.cgi"
fi
echo

echo "-- dynamicui sidebar (groupe MegaStats) --"
if [[ -f "$DYNAMICUI" ]]; then
    echo "OK : $DYNAMICUI"
    grep -E 'MegaStats|megastats' "$DYNAMICUI" 2>/dev/null || true
else
    echo "WARN : dynamicui absent — ./whm/install-dynamicui.sh puis rebuild-menu.sh"
fi
echo

echo "-- Icône --"
ls -la "$ICON" 2>/dev/null || echo "icône absente"
if [[ -f "$ICON" ]]; then
    file "$ICON" 2>/dev/null || true
fi
echo

echo "-- AppConfig fichier --"
cat /var/cpanel/apps/megastats.conf 2>/dev/null || echo "absent"
echo

echo "-- Accès WHM --"
echo "Menu Plugins : addon_megastats.cgi → megastats/index.cgi"
echo "Sidebar      : groupe MegaStats (dynamicui)"
echo "Recherche    : MegaStats (AppConfig searchtext)"
echo
echo "Test auth : /cpsessXXXXXXXX/cgi/megastats/index.cgi?whmtest=1"
