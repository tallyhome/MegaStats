#!/bin/bash
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$(cd "$PLUGIN_DIR/.." && pwd)"
INSTALL_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/megastats"
CGI_ROOT="/usr/local/cpanel/whostmgr/docroot/cgi"

bash "$PLUGIN_DIR/write-entry.sh" "$CGI_ROOT"

bash "$PLUGIN_DIR/install-icon.sh"
bash "$PLUGIN_DIR/install-dynamicui.sh"
cp "$PLUGIN_DIR/app.php" "$INSTALL_DIR/app.php"
cp "$PLUGIN_DIR/app.cgi" "$INSTALL_DIR/app.cgi"
bash "$PLUGIN_DIR/normalize-deploy.sh" \
    "$INSTALL_DIR/app.cgi" \
    "$INSTALL_DIR/app.php" \
    ""
rm -f "$CGI_ROOT/megastats_test.cgi" "$CGI_ROOT/megastats_addon.cgi"
rsync -a "$SRC_DIR/includes/" "$INSTALL_DIR/includes/"
rsync -a "$SRC_DIR/templates/" "$INSTALL_DIR/templates/"
rsync -a "$SRC_DIR/config/" "$INSTALL_DIR/config/"

/usr/local/cpanel/bin/unregister_appconfig megastats 2>/dev/null || true
/usr/local/cpanel/bin/register_appconfig "$PLUGIN_DIR/megastats.conf"
bash "$PLUGIN_DIR/rebuild-menu.sh"
/scripts/restartsrv_cpsrvd --wait 2>/dev/null || true

echo "Enregistrement : $(/usr/local/cpanel/bin/is_registered_with_appconfig whostmgr megastats)"
echo "OK — addon_megastats.cgi = menu Plugins ; megastats/index.cgi = app ; groupe sidebar dynamicui"
