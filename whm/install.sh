#!/bin/bash
#
# MegaStats — WHM plugin installer
#
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$(cd "$PLUGIN_DIR/.." && pwd)"
INSTALL_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/megastats"
CGI_ROOT="/usr/local/cpanel/whostmgr/docroot/cgi"
DATA_DIR="/var/cpanel/megastats"
CRON_FILE="/etc/cron.d/megastats"

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez ce script en root." >&2
    exit 1
fi

PHP_BIN=""
PHP_CGI=""
for candidate in /usr/local/bin/ea-php82 /usr/local/bin/ea-php83 /usr/local/bin/php; do
    if [[ -x "$candidate" ]]; then
        PHP_BIN="$candidate"
        break
    fi
done
for candidate in /usr/local/cpanel/3rdparty/bin/php-cgi /usr/local/bin/ea-php82-cgi /usr/local/bin/ea-php83-cgi; do
    if [[ -x "$candidate" ]]; then
        PHP_CGI="$candidate"
        break
    fi
done

echo "==> MegaStats WHM — installation"
echo "    PHP cron : $PHP_BIN"
echo "    PHP CGI  : ${PHP_CGI:-MANQUANT — auth WHM impossible}"

if [[ -z "$PHP_CGI" ]]; then
    echo "ERREUR : php-cgi introuvable. Installez ea-php82-cgi via EasyApache." >&2
    exit 1
fi

mkdir -p "$DATA_DIR"/{logs,metrics,cache}
WEBGROUP="nobody"
if getent group "$WEBGROUP" >/dev/null 2>&1; then
    chown -R root:"$WEBGROUP" "$DATA_DIR"
    chmod 775 "$DATA_DIR" "$DATA_DIR/logs" "$DATA_DIR/metrics" "$DATA_DIR/cache"
fi

mkdir -p "$INSTALL_DIR"
rsync -a --delete \
    --exclude 'whm/' \
    --exclude 'storage/' \
    --exclude '.git/' \
    --exclude 'MODERNIZATION.md' \
    "$SRC_DIR/" "$INSTALL_DIR/"

cp "$PLUGIN_DIR/app.php" "$INSTALL_DIR/app.php"
cp "$PLUGIN_DIR/app.cgi" "$INSTALL_DIR/app.cgi"
touch "$INSTALL_DIR/.whm-deployment"

bash "$PLUGIN_DIR/write-entry.sh" "$CGI_ROOT"

bash "$PLUGIN_DIR/normalize-deploy.sh" \
    "$INSTALL_DIR/app.cgi" \
    "$INSTALL_DIR/app.php" \
    ""

bash "$PLUGIN_DIR/install-icon.sh"
bash "$PLUGIN_DIR/install-dynamicui.sh"

rm -f "$CGI_ROOT/megastats_menu.cgi" "$CGI_ROOT/megastats_test.cgi" "$CGI_ROOT/megastats_addon.cgi" "$CGI_ROOT/megastats.cgi"

[[ -d /var/cpanel/apps ]] || mkdir -p /var/cpanel/apps
chmod 755 /var/cpanel/apps

/usr/local/cpanel/bin/unregister_appconfig megastats 2>/dev/null || true
/usr/local/cpanel/bin/register_appconfig "$PLUGIN_DIR/megastats.conf"

bash "$PLUGIN_DIR/rebuild-menu.sh"

/scripts/restartsrv_cpsrvd --wait 2>/dev/null || true

cat > "$CRON_FILE" <<EOF
* * * * * root $PHP_BIN $INSTALL_DIR/cron.php >/dev/null 2>&1
EOF
chmod 644 "$CRON_FILE"

HOST="$(hostname -f 2>/dev/null || hostname)"

echo
echo "Installation terminée."
echo "  Vérification : /usr/local/cpanel/bin/is_registered_with_appconfig whostmgr megastats"
/usr/local/cpanel/bin/is_registered_with_appconfig whostmgr megastats || true
echo
echo "  Accès : Plugins (addon_megastats.cgi) + sidebar MegaStats + recherche WHM"
echo "  URL app : /cpsess.../cgi/megastats/index.cgi"
echo "  Mise à jour : ./whm/update.sh"
echo "  Diagnostic : $PLUGIN_DIR/diagnose.sh"