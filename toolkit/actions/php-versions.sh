#!/bin/bash
set -euo pipefail

echo "=== Versions PHP (EasyApache) ==="
if [[ -d /opt/cpanel/ea-php*/root/usr/bin ]]; then
    ls -d /opt/cpanel/ea-php* 2>/dev/null | while read -r d; do
        ver="$(basename "$d")"
        bin="$d/root/usr/bin/php"
        if [[ -x "$bin" ]]; then
            echo "$ver : $($bin -v 2>/dev/null | head -1)"
        fi
    done
elif command -v whmapi1 >/dev/null 2>&1; then
    whmapi1 php_get_installed_versions 2>/dev/null | head -40 || true
else
    php -v 2>/dev/null | head -1 || echo "PHP non détecté."
fi
