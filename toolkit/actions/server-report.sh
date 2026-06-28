#!/bin/bash
# Rapport serveur — Server Toolkit
set -euo pipefail

echo "=== OBI2 Server Toolkit — Rapport serveur ==="
echo "Date      : $(date '+%Y-%m-%d %H:%M:%S %Z')"
echo "Hostname  : $(hostname -f 2>/dev/null || hostname)"
echo "Uptime    : $(uptime -p 2>/dev/null || uptime)"
echo "Load      : $(awk '{print $1,$2,$3}' /proc/loadavg 2>/dev/null || echo '?')"
echo "Kernel    : $(uname -sr 2>/dev/null || echo '?')"
echo

if [[ -f /usr/local/cpanel/cpanel ]]; then
    echo "cPanel    : $(/usr/local/cpanel/cpanel -V 2>/dev/null | head -1 || echo '?')"
fi

if [[ -d /var/cpanel/users ]]; then
    echo "Comptes   : $(find /var/cpanel/users -maxdepth 1 -type f 2>/dev/null | wc -l | tr -d ' ')"
fi

echo
echo "--- Mémoire ---"
free -h 2>/dev/null || true

echo
echo "--- Disque (/) ---"
df -h / 2>/dev/null | tail -1 || true

echo
echo "=== Fin rapport ==="
