#!/bin/bash
# Entrée sidebar WHM garantie (groupe MegaStats) — indépendante de pluginscache.yaml.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
THEME_X="/usr/local/cpanel/whostmgr/docroot/themes/x"
TARGET_DIR="$THEME_X/dynamicui"
TARGET_FILE="$TARGET_DIR/dynamicui_megastats.conf"

mkdir -p "$TARGET_DIR"
install -o root -g root -m 0644 "$PLUGIN_DIR/dynamicui_megastats.conf" "$TARGET_FILE"

# Compatibilité : certains builds lisent aussi themes/x/*.conf directement
install -o root -g root -m 0644 "$PLUGIN_DIR/dynamicui_megastats.conf" "$THEME_X/dynamicui_megastats.conf"

echo "OK : dynamicui MegaStats → $TARGET_FILE"
