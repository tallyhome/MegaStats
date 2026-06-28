#!/bin/bash
# MegaStats — plugin cPanel (réputation mail utilisateur)
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$(cd "$PLUGIN_DIR/.." && pwd)"
CPANEL_DIR="/usr/local/cpanel/base/3rdparty/megastats"

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : root requis." >&2
    exit 1
fi

echo "==> MegaStats cPanel Mail"

mkdir -p "$CPANEL_DIR"
rsync -a \
    --exclude 'whm/' --exclude 'cpanel/' --exclude 'storage/' --exclude '.git/' \
    "$SRC_DIR/includes/" "$CPANEL_DIR/includes/"
rsync -a "$SRC_DIR/templates/" "$CPANEL_DIR/templates/"
rsync -a "$SRC_DIR/config/" "$CPANEL_DIR/config/"
rsync -a "$SRC_DIR/assets/" "$CPANEL_DIR/assets/"
cp "$PLUGIN_DIR/mail.php" "$CPANEL_DIR/mail.php"
cp "$PLUGIN_DIR/mail.cgi" "$CPANEL_DIR/mail.cgi"
chmod 755 "$CPANEL_DIR/mail.cgi"

if [[ -f "$PLUGIN_DIR/../whm/megastats.png" ]]; then
    cp "$PLUGIN_DIR/../whm/megastats.png" "$CPANEL_DIR/megastats.png" 2>/dev/null || true
fi

/usr/local/cpanel/bin/unregister_appconfig megastats_mail 2>/dev/null || true
/usr/local/cpanel/bin/register_appconfig "$PLUGIN_DIR/megastats-cpanel.conf"

echo "OK — cPanel : Réputation mail → /3rdparty/megastats/mail.cgi"
