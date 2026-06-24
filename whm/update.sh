#!/bin/bash
# Mise à jour MegaStats depuis Git puis réinstallation WHM (menu inclus).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$(cd "$PLUGIN_DIR/.." && pwd)"
INSTALL_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/megastats"

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez en root." >&2
    exit 1
fi

echo "==> MegaStats — mise à jour"

UPDATED=0

if [[ -d "$SRC_DIR/.git" ]]; then
    echo "    Source : git pull dans $SRC_DIR"
    git -C "$SRC_DIR" pull --ff-only
    UPDATED=1
elif [[ -d "$INSTALL_DIR/.git" ]]; then
    echo "    Install : git pull dans $INSTALL_DIR"
    git -C "$INSTALL_DIR" pull --ff-only
    SRC_DIR="$INSTALL_DIR"
    UPDATED=1
else
    RELEASE_URL=""
    if [[ -f "$SRC_DIR/config/distribution.php" ]]; then
        RELEASE_URL="$(grep -oP "'release_url'\s*=>\s*'\K[^']+" "$SRC_DIR/config/distribution.php" 2>/dev/null || true)"
    fi
    RELEASE_URL="${MEGASTATS_RELEASE_URL:-$RELEASE_URL}"

    if [[ -n "$RELEASE_URL" ]]; then
        TMP="$(mktemp -d)"
        echo "    Téléchargement : $RELEASE_URL"
        curl -fsSL "$RELEASE_URL" -o "$TMP/archive.tar.gz"
        tar -xzf "$TMP/archive.tar.gz" -C "$TMP"
        EXTRACTED="$(find "$TMP" -maxdepth 1 -type d -name 'megastats*' | head -1)"
        if [[ -z "$EXTRACTED" || ! -d "$EXTRACTED" ]]; then
            echo "ERREUR : archive invalide" >&2
            rm -rf "$TMP"
            exit 1
        fi
        rsync -a --delete \
            --exclude '.git' \
            --exclude 'storage/' \
            "$EXTRACTED/" "$SRC_DIR/"
        rm -rf "$TMP"
        UPDATED=1
    fi
fi

if [[ "$UPDATED" -eq 0 ]]; then
    echo "WARN : pas de dépôt git ni release_url — réinstallation des fichiers locaux uniquement."
fi

bash "$PLUGIN_DIR/install.sh"
echo
echo "Mise à jour terminée."
