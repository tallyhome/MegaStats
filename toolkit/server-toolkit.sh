#!/bin/bash
# OBI2 Server Toolkit v1.0 — menu interactif (root / WHM)
set -euo pipefail

TOOLKIT_DIR="$(cd "$(dirname "$0")" && pwd)"
ACTIONS="$TOOLKIT_DIR/actions"

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Erreur : exécutez en root." >&2
    exit 1
fi

run_action() {
    local script="$1"
    if [[ -x "$ACTIONS/$script" ]]; then
        bash "$ACTIONS/$script"
    else
        echo ">>> Fonction en cours de développement."
        echo "    Script attendu : $ACTIONS/$script"
    fi
    echo
    read -r -p "Entrée pour continuer…" _
}

show_header() {
    clear
    echo "========================================="
    echo "      OBI2 SERVER TOOLKIT v1.0"
    echo "========================================="
    echo
}

show_main_menu() {
    show_header
    cat <<'MENU'
1  - Déplacer un compte
2  - Changer le propriétaire
3  - Changer l'IP
4  - Déplacer tous les comptes d'un revendeur
5  - Vérifier les incohérences
6  - Créer un nouveau compte
7  - Installer Laravel
8  - Installer WordPress
9  - Corriger les permissions
10 - Rapport serveur

--- Modules étendus ---
11 - Audit complet du serveur
12 - Statistiques
13 - Sauvegardes (bientôt)
14 - SSL
15 - Exim
16 - DNS
17 - Espace disque
18 - Git (bientôt)
19 - Docker (bientôt)
20 - NodeJS (bientôt)
21 - Versions PHP
22 - Firewall (bientôt)
23 - IA erreurs (bientôt)

0  - Quitter
MENU
    echo
}

interactive_stub() {
    local title="$1"
    echo "=== $title ==="
    echo "Assistant interactif — utilisez WHM ou whmapi1 pour cette opération."
    echo "MegaStats WHM : Plugins → MegaStats → Server Toolkit (actions web)."
    echo
    read -r -p "Nom du compte (optionnel) : " acct || true
    [[ -n "${acct:-}" ]] && echo "Compte saisi : $acct"
}

while true; do
    show_main_menu
    read -r -p "Choix : " choice || exit 0
    case "$choice" in
        0) echo "Au revoir."; exit 0 ;;
        1) interactive_stub "Déplacer un compte" ;;
        2) interactive_stub "Changer le propriétaire" ;;
        3) interactive_stub "Changer l'IP" ;;
        4) interactive_stub "Déplacer comptes revendeur" ;;
        5) run_action "check-inconsistencies.sh" ;;
        6) interactive_stub "Créer un compte" ;;
        7) echo ">>> Laravel — bientôt"; read -r -p "Entrée…" _ ;;
        8) echo ">>> WordPress — bientôt"; read -r -p "Entrée…" _ ;;
        9) interactive_stub "Corriger les permissions (/scripts/fixperms)" ;;
        10) run_action "server-report.sh" ;;
        11) run_action "full-audit.sh" ;;
        12) run_action "statistics.sh" ;;
        13) echo ">>> Sauvegardes — bientôt"; read -r -p "Entrée…" _ ;;
        14) run_action "ssl-status.sh" ;;
        15) run_action "exim-status.sh" ;;
        16) run_action "dns-status.sh" ;;
        17) run_action "disk-space.sh" ;;
        18|19|20|22|23) echo ">>> Module bientôt disponible"; read -r -p "Entrée…" _ ;;
        21) run_action "php-versions.sh" ;;
        *) echo "Choix invalide."; sleep 1 ;;
    esac
done
