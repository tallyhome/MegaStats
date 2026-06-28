#!/bin/bash
set -euo pipefail

echo "=== Exim ==="

version=""
if command -v exim >/dev/null 2>&1; then
    version=$(exim -bV 2>/dev/null | head -1 | grep -oP 'Exim version \K[^\s]+' || exim -bV 2>/dev/null | head -1)
fi
echo "Version"
echo "${version:-?}"
echo

main_ip=""
[[ -f /var/cpanel/mainip ]] && main_ip=$(cat /var/cpanel/mainip)

mailips_ok=0
if [[ -f /etc/mailips ]] && [[ -s /etc/mailips ]]; then
    mailips_ok=1
    echo "Outgoing IP"
    echo "✔ IP dédiées (mailips)"
else
    echo "Outgoing IP"
    echo "❌ Utilise l'IP principale (${main_ip:-?})"
fi
echo

echo "mailips"
if [[ $mailips_ok -eq 1 ]]; then
    echo "✔ configuré ($(wc -l < /etc/mailips | tr -d ' ') entrée(s))"
else
    echo "❌ Vide"
fi
echo

echo "mailhelo"
if [[ -f /etc/mailhelo ]] && [[ -s /etc/mailhelo ]]; then
    echo "✔ configuré"
else
    echo "❌ Vide"
fi
echo

echo "Send mail from account IP"
if [[ -f /var/cpanel/cpanel.config ]] && grep -q '^sendmailfromaccountip=1' /var/cpanel/cpanel.config 2>/dev/null; then
    echo "✔ activé"
else
    echo "❌ désactivé"
fi
echo

echo "Résultat"
issues=0
[[ $mailips_ok -eq 0 ]] && issues=$((issues + 1))
[[ ! -f /etc/mailhelo ]] || [[ ! -s /etc/mailhelo ]] && issues=$((issues + 1))
if [[ -f /var/cpanel/cpanel.config ]] && ! grep -q '^sendmailfromaccountip=1' /var/cpanel/cpanel.config 2>/dev/null; then
    issues=$((issues + 1))
fi
if [[ $issues -gt 0 ]]; then
    echo "⚠ Incohérence détectée"
else
    echo "✔ Configuration cohérente"
fi

echo
if command -v exim >/dev/null 2>&1; then
    echo "Queue: $(exim -bpc 2>/dev/null || echo '?') message(s)"
fi
