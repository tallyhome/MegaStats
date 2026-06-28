#!/bin/bash
# Mise à jour MegaStats depuis Git puis réinstallation WHM (menu inclus).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$(cd "$PLUGIN_DIR/.." && pwd)"
INSTALL_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/megastats"

chmod +x "$PLUGIN_DIR"/*.sh 2>/dev/null || true
if [[ -d "$SRC_DIR/toolkit" ]]; then
    chmod +x "$SRC_DIR/toolkit/server-toolkit.sh" 2>/dev/null || true
    chmod +x "$SRC_DIR/toolkit/actions"/*.sh 2>/dev/null || true
fi

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez en root." >&2
    exit 1
fi

read_git_branch() {
    local dir="$1"
    if [[ -f "$dir/config/distribution.php" ]]; then
        grep -oP "'git_branch'\s*=>\s*'\K[^']+" "$dir/config/distribution.php" 2>/dev/null || echo "main"
    else
        echo "main"
    fi
}

megastats_git_sync() {
    local repo_dir="$1"
    local branch="${MEGASTATS_GIT_BRANCH:-$(read_git_branch "$repo_dir")}"

    echo "    fetch origin (branche : $branch)"
    git -C "$repo_dir" fetch origin --tags --prune

    if ! git -C "$repo_dir" show-ref --verify --quiet "refs/remotes/origin/$branch"; then
        echo "ERREUR : origin/$branch introuvable. Vérifiez le dépôt distant." >&2
        return 1
    fi

    echo "    reset sur origin/$branch (ignore les modifications locales)"
    git -C "$repo_dir" checkout -B "$branch" "origin/$branch" -f
    git -C "$repo_dir" reset --hard "origin/$branch"
    git -C "$repo_dir" clean -fd

    return 0
}

megastats_download_release() {
    local dest_dir="$1"
    local release_url=""

    if [[ -f "$dest_dir/config/distribution.php" ]]; then
        release_url="$(grep -oP "'release_url'\s*=>\s*'\K[^']+" "$dest_dir/config/distribution.php" 2>/dev/null || true)"
    fi
    release_url="${MEGASTATS_RELEASE_URL:-$release_url}"

    if [[ -z "$release_url" ]]; then
        return 1
    fi

    local tmp
    tmp="$(mktemp -d)"
    echo "    Téléchargement : $release_url"
    curl -fsSL "$release_url" -o "$tmp/archive.tar.gz"
    tar -xzf "$tmp/archive.tar.gz" -C "$tmp"
    local extracted
    extracted="$(find "$tmp" -maxdepth 1 -type d -name 'MegaStats*' -o -name 'megastats*' 2>/dev/null | head -1)"
    if [[ -z "$extracted" || ! -d "$extracted" ]]; then
        extracted="$(find "$tmp" -maxdepth 1 -mindepth 1 -type d | head -1)"
    fi
    if [[ -z "$extracted" || ! -d "$extracted" ]]; then
        echo "ERREUR : archive invalide" >&2
        rm -rf "$tmp"
        return 1
    fi
    rsync -a --delete \
        --exclude '.git' \
        --exclude 'storage/' \
        "$extracted/" "$dest_dir/"
    rm -rf "$tmp"
    return 0
}

echo "==> MegaStats — mise à jour"

UPDATED=0
GIT_DIR=""

if [[ -d "$SRC_DIR/.git" ]]; then
    GIT_DIR="$SRC_DIR"
elif [[ -d "$INSTALL_DIR/.git" ]]; then
    GIT_DIR="$INSTALL_DIR"
    SRC_DIR="$INSTALL_DIR"
fi

if [[ -n "$GIT_DIR" ]]; then
    echo "    Dépôt git : $GIT_DIR"
    if megastats_git_sync "$GIT_DIR"; then
        UPDATED=1
    else
        echo "WARN : git sync échoué — tentative archive GitHub…"
        if megastats_download_release "$SRC_DIR"; then
            UPDATED=1
        fi
    fi
elif megastats_download_release "$SRC_DIR"; then
    UPDATED=1
fi

if [[ "$UPDATED" -eq 0 ]]; then
    echo "WARN : pas de dépôt git ni release — réinstallation des fichiers locaux uniquement."
fi

bash "$PLUGIN_DIR/install.sh"
echo
if [[ -f "$INSTALL_DIR/config/app.php" ]]; then
    echo "Version installée : $(grep -oP "'version'\s*=>\s*'\K[^']+" "$INSTALL_DIR/config/app.php" 2>/dev/null || echo '?')"
fi
echo "Mise à jour terminée."
