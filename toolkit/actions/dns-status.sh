#!/bin/bash
set -euo pipefail

echo "=== DNS (named) ==="
if systemctl is-active named >/dev/null 2>&1 || pgrep -x named >/dev/null 2>&1; then
    echo "named: actif"
else
    echo "named: inactif ou non détecté"
fi

if [[ -f /etc/resolv.conf ]]; then
    echo "Résolveurs:"
    grep -E '^nameserver' /etc/resolv.conf 2>/dev/null || true
fi
