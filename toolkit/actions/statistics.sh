#!/bin/bash
set -euo pipefail

echo "=== Statistiques serveur ==="
echo "Connexions TCP établies: $(ss -H state established 2>/dev/null | wc -l | tr -d ' ')"
echo "Processus: $(ps aux 2>/dev/null | wc -l | tr -d ' ')"
if [[ -d /var/cpanel/users ]]; then
    echo "Comptes cPanel: $(ls /var/cpanel/users 2>/dev/null | wc -l | tr -d ' ')"
fi
echo
echo "Voir aussi MegaStats → graphiques CPU/RAM/réseau."
