#!/bin/bash
# Install WHM sidebar icon (required for menu / Plugins section)
set -euo pipefail

ICON_DIR="/usr/local/cpanel/whostmgr/docroot/addon_plugins"
ICON_FILE="$ICON_DIR/megastats.png"

mkdir -p "$ICON_DIR"

installed=0
for src in \
    /usr/local/cpanel/whostmgr/docroot/addon_plugins/ico-security-advisor.png \
    /usr/local/cpanel/whostmgr/docroot/themes/x/icons/stats_48.png \
    /usr/local/cpanel/whostmgr/docroot/themes/x/icons/statistics.png \
    /usr/local/cpanel/whostmgr/docroot/images/stats.png; do
    if [[ -f "$src" ]]; then
        cp "$src" "$ICON_FILE"
        installed=1
        echo "Icon copied from $src"
        break
    fi
done

if [[ "$installed" -eq 0 ]]; then
    PHP_BIN="/usr/local/bin/ea-php82"
    [[ -x "$PHP_BIN" ]] || PHP_BIN="/usr/local/bin/ea-php83"
    if [[ -x "$PHP_BIN" ]] && "$PHP_BIN" -r 'exit(function_exists("imagecreatetruecolor")?0:1);'; then
        "$PHP_BIN" -r '
            $im = imagecreatetruecolor(48, 48);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefill($im, 0, 0, $transparent);
            $blue = imagecolorallocate($im, 13, 110, 253);
            imagefilledrectangle($im, 4, 4, 43, 43, $blue);
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefilledrectangle($im, 12, 26, 36, 30, $white);
            imagefilledrectangle($im, 12, 18, 18, 30, $white);
            imagefilledrectangle($im, 24, 12, 28, 30, $white);
            imagefilledrectangle($im, 32, 20, 36, 30, $white);
            imagepng($im, "/usr/local/cpanel/whostmgr/docroot/addon_plugins/megastats.png");
        '
        echo "Icon generated with PHP GD"
        installed=1
    fi
fi

if [[ "$installed" -eq 0 ]]; then
    echo "ERROR: could not create megastats.png — install php-gd or copy icon manually to $ICON_FILE" >&2
    exit 1
fi

chmod 644 "$ICON_FILE"
chown root:root "$ICON_FILE"
ls -la "$ICON_FILE"
