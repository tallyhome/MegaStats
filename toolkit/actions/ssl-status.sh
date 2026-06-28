#!/bin/bash
set -euo pipefail

echo "=== SSL / AutoSSL (aperçu) ==="
if [[ -x /usr/local/cpanel/bin/autossl_check ]]; then
    /usr/local/cpanel/bin/autossl_check --all 2>&1 | head -30 || true
elif command -v whmapi1 >/dev/null 2>&1; then
    whmapi1 get_ssl_certificate_for_domain domain="$(hostname -f 2>/dev/null)" 2>/dev/null | head -20 || echo "Utilisez WHM → SSL/TLS Status pour le détail."
else
    echo "Outils SSL cPanel non disponibles."
fi
