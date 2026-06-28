#!/bin/bash
# Statut DNS — BIND (named) et PowerDNS (cPanel)
set -uo pipefail

echo "=== DNS ==="

dns_service_active() {
    local svc="$1"
    systemctl is-active "$svc" >/dev/null 2>&1
}

dns_process_active() {
    pgrep -x "$1" >/dev/null 2>&1
}

CPANEL_BACKEND=""
if [[ -f /var/cpanel/cpanel.config ]]; then
    CPANEL_BACKEND=$(grep -E '^local_authoritative_nameserver=' /var/cpanel/cpanel.config 2>/dev/null \
        | head -1 | cut -d= -f2- | tr -d "'\" " || true)
fi

PDNS_ACTIVE=0
NAMED_ACTIVE=0
ANY_ACTIVE=0

# PowerDNS (cPanel « Local DNS » = powerdns)
if dns_service_active pdns \
    || dns_process_active pdns_server \
    || pgrep -f '[/]pdns_server' >/dev/null 2>&1; then
    PDNS_ACTIVE=1
    ANY_ACTIVE=1
    echo "PowerDNS (pdns): actif"
else
    if [[ "$CPANEL_BACKEND" == "powerdns" ]]; then
        echo "PowerDNS (pdns): inactif (backend cPanel = powerdns)"
    elif [[ -x /usr/sbin/pdns_server ]] \
        || [[ -x /usr/local/cpanel/3rdparty/sbin/pdns_server ]] \
        || dns_service_active pdns 2>/dev/null; then
        echo "PowerDNS (pdns): inactif ou non démarré"
    fi
fi

# BIND / named (legacy ou DNSONLY)
if dns_service_active named || dns_process_active named; then
    NAMED_ACTIVE=1
    ANY_ACTIVE=1
    echo "BIND (named): actif"
else
    if [[ "$CPANEL_BACKEND" == "bind" ]] || [[ -z "$CPANEL_BACKEND" ]]; then
        if [[ "$PDNS_ACTIVE" -eq 0 ]]; then
            echo "BIND (named): inactif ou non détecté"
        fi
    fi
fi

if [[ -n "$CPANEL_BACKEND" ]]; then
    echo "Backend cPanel configuré : $CPANEL_BACKEND"
fi

if [[ "$ANY_ACTIVE" -eq 1 ]]; then
    echo "Statut global : DNS actif"
else
    echo "Statut global : aucun serveur DNS local détecté (vérifiez pdns ou named)"
fi

if [[ -x /scripts/restartsrv_dns ]]; then
    echo
    echo "--- cPanel restartsrv_dns ---"
    /scripts/restartsrv_dns --status 2>/dev/null | sed 's/^/  /' || true
fi

if command -v whmapi1 >/dev/null 2>&1; then
    for svc in pdns named; do
        status=$(whmapi1 --output=json servicestatus "service=$svc" 2>/dev/null \
            | grep -oP '"status"\s*:\s*"\K[^"]+' | head -1 || true)
        if [[ -n "$status" ]]; then
            echo "whmapi1 servicestatus $svc : $status"
        fi
    done
fi

echo
if [[ -f /etc/resolv.conf ]]; then
    echo "Résolveurs (/etc/resolv.conf):"
    grep -E '^nameserver' /etc/resolv.conf 2>/dev/null | sed 's/^/  /' || echo "  (aucun)"
fi
