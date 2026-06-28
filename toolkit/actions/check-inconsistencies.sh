#!/bin/bash
set -euo pipefail

echo "=== Vérification incohérences (basique) ==="

if [[ ! -d /var/cpanel/users ]]; then
    echo "Pas un serveur cPanel standard (/var/cpanel/users absent)."
    exit 0
fi

issues=0

if [[ -f /etc/wwwacct.conf ]]; then
    main_ip="$(grep '^ADDR ' /etc/wwwacct.conf 2>/dev/null | awk '{print $2}' || true)"
    [[ -n "$main_ip" ]] && echo "IP principale (wwwacct): $main_ip"
fi

if [[ -f /var/cpanel/mainip ]]; then
    echo "mainip: $(cat /var/cpanel/mainip 2>/dev/null)"
fi

echo
echo "Comptes sans homedir:"
while IFS= read -r u; do
    [[ -z "$u" ]] && continue
    home="$(grep -E "^${u}:" /etc/passwd 2>/dev/null | cut -d: -f6 || true)"
    if [[ -n "$home" && ! -d "$home" ]]; then
        echo "  - $u ($home manquant)"
        issues=$((issues + 1))
    fi
done < <(ls /var/cpanel/users 2>/dev/null)

echo
if [[ "$issues" -eq 0 ]]; then
    echo "Aucune incohérence évidente détectée (contrôle basique)."
else
    echo "$issues problème(s) détecté(s). Audit manuel recommandé."
    exit 1
fi
