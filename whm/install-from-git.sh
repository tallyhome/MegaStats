#!/bin/bash
# Installation initiale depuis Git (root, WHM).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
DEFAULT_DIR="/opt/megastats"
TARGET_DIR="${1:-$DEFAULT_DIR}"

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez en root." >&2
    exit 1
fi

# URL depuis config/distribution.php si le dépôt est déjà présent
GIT_REPO=""
if [[ -f "$PLUGIN_DIR/../config/distribution.php" ]]; then
    GIT_REPO="$(grep -oP "'git_repo'\s*=>\s*'\K[^']+" "$PLUGIN_DIR/../config/distribution.php" 2>/dev/null || true)"
fi
GIT_REPO="${MEGASTATS_GIT_REPO:-${GIT_REPO:-https://github.com/tallyhome/MegaStats.git}}"

if [[ -d "$TARGET_DIR/.git" ]]; then
    echo "Dépôt existant : $TARGET_DIR — lancement install.sh"
    bash "$TARGET_DIR/whm/install.sh"
    exit 0
fi

if [[ -d "$TARGET_DIR" && "$(ls -A "$TARGET_DIR" 2>/dev/null)" ]]; then
    echo "Erreur : $TARGET_DIR existe et n'est pas vide." >&2
    exit 1
fi

mkdir -p "$(dirname "$TARGET_DIR")"
if command -v git >/dev/null 2>&1; then
    git clone --depth 1 "$GIT_REPO" "$TARGET_DIR"
else
    echo "git absent — définissez MEGASTATS_RELEASE_URL ou installez git." >&2
    exit 1
fi

bash "$TARGET_DIR/whm/install.sh"
echo
echo "OK — MegaStats installé dans $TARGET_DIR"
