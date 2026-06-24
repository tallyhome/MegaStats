#!/bin/bash
# Reconstruit pluginscache + chrome navigation WHM (menu Plugins + groupe MegaStats).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
ADDON_DIR="/usr/local/cpanel/whostmgr/addonfeatures"
CGI_ROOT="/usr/local/cpanel/whostmgr/docroot/cgi"

mkdir -p "$ADDON_DIR"
touch "$ADDON_DIR/megastats"
echo "all" > "$ADDON_DIR/megastats.acl"
chmod 644 "$ADDON_DIR/megastats" "$ADDON_DIR/megastats.acl"

rm -f \
    "$CGI_ROOT/megastats.cgi" \
    "$CGI_ROOT/megastats_menu.cgi" \
    "$CGI_ROOT/megastats_addon.cgi"

bash "$PLUGIN_DIR/install-icon.sh"
bash "$PLUGIN_DIR/install-dynamicui.sh"

if [[ -x /scripts/autorepair ]]; then
    /scripts/autorepair fix_addon_features 2>/dev/null || true
fi

if [[ -x /usr/local/cpanel/bin/rebuild_sprites ]]; then
    /usr/local/cpanel/bin/rebuild_sprites 2>/dev/null || true
fi

if [[ -x /scripts/updatepluginlist ]]; then
    /scripts/updatepluginlist 2>/dev/null || true
fi

rm -f /var/cpanel/pluginscache.yaml

if [[ -x /usr/local/cpanel/bin/refresh_plugins_cache ]]; then
    /usr/local/cpanel/bin/refresh_plugins_cache 2>/dev/null || true
fi

for perl_bin in \
    /usr/local/cpanel/3rdparty/perl/536/bin/perl \
    /usr/local/cpanel/3rdparty/perl/534/bin/perl \
    /usr/local/cpanel/3rdparty/bin/perl; do
    if [[ -x "$perl_bin" ]]; then
        "$perl_bin" -MWhostmgr::Plugins -e 'Whostmgr::Plugins->plugins_data' 2>/dev/null && break
    fi
done

if [[ -x /scripts/rebuild_whm_chrome ]]; then
    echo "==> rebuild_whm_chrome (menu gauche WHM)..."
    /scripts/rebuild_whm_chrome 2>/dev/null || true
fi

echo
echo "addon_megastats.cgi (menu Plugins — convention cPanel) :"
head -3 "$CGI_ROOT/addon_megastats.cgi" 2>/dev/null || echo "MANQUANT — ./whm/write-entry.sh"
echo "megastats/index.cgi (app PHP) :"
head -1 "$CGI_ROOT/megastats/index.cgi" 2>/dev/null || echo "MANQUANT — ./whm/write-entry.sh"

if [[ -f /var/cpanel/pluginscache.yaml ]]; then
    echo "pluginscache.yaml :"
    grep -i megastats /var/cpanel/pluginscache.yaml 2>/dev/null || echo "(megastats absent — vérifier addon_megastats.cgi)"
else
    echo "WARN : pluginscache.yaml absent"
fi

if [[ -f /usr/local/cpanel/whostmgr/docroot/themes/x/dynamicui/dynamicui_megastats.conf ]]; then
    echo "OK : dynamicui sidebar MegaStats installé"
else
    echo "WARN : dynamicui absent — ./whm/install-dynamicui.sh"
fi
