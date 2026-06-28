#!/bin/bash
set -euo pipefail

echo "=== Audit complet serveur ==="
echo "Date: $(date)"
echo

bash "$(dirname "$0")/server-report.sh" 2>/dev/null || true
echo
bash "$(dirname "$0")/disk-space.sh" 2>/dev/null || true
echo
bash "$(dirname "$0")/exim-status.sh" 2>/dev/null || true
echo
bash "$(dirname "$0")/ssl-status.sh" 2>/dev/null || true
echo
bash "$(dirname "$0")/dns-status.sh" 2>/dev/null || true
echo
bash "$(dirname "$0")/check-inconsistencies.sh" 2>/dev/null || true

echo
echo "=== Fin audit ==="
