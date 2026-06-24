#!/bin/bash
# Convention cPanel (Security Advisor, ModSecurity, etc.) :
#   addon_megastats.cgi  = Perl #WHMADDON → menu Plugins (pluginscache.yaml)
#   megastats/index.cgi  = PHP-CGI (AppConfig url)
set -euo pipefail

CGI_ROOT="${1:-/usr/local/cpanel/whostmgr/docroot/cgi}"
MENU_CGI="$CGI_ROOT/addon_megastats.cgi"
APP_CGI="$CGI_ROOT/megastats/index.cgi"
PERL_BIN="/usr/local/cpanel/3rdparty/bin/perl"

mkdir -p "$CGI_ROOT/megastats"

cat > "$MENU_CGI" <<'MEGAEOF'
#!/usr/local/cpanel/3rdparty/bin/perl
#WHMADDON:megastats:MegaStats:megastats.png
#ACLS:all

use strict;
use warnings;

my $query = defined $ENV{QUERY_STRING} && $ENV{QUERY_STRING} ne '' ? '?' . $ENV{QUERY_STRING} : '';

print "Status: 302 Found\r\n";
print "Location: megastats/index.cgi$query\r\n\r\n";
exit 0;
MEGAEOF

cat > "$APP_CGI" <<'MEGAEOF'
#!/usr/local/cpanel/3rdparty/bin/php-cgi
<?php

require __DIR__ . '/app.php';
MEGAEOF

chmod 755 "$MENU_CGI" "$APP_CGI"
chown root:root "$MENU_CGI" "$APP_CGI"

# Anciens chemins (ne sont pas scannés par Whostmgr::Plugins)
rm -f \
    "$CGI_ROOT/megastats.cgi" \
    "$CGI_ROOT/megastats_addon.cgi" \
    "$CGI_ROOT/megastats_menu.cgi"

for target in "$MENU_CGI" "$APP_CGI"; do
    if grep -q $'\r' "$target"; then
        echo "ERREUR : CRLF dans $target" >&2
        exit 1
    fi
done

if [[ -x "$PERL_BIN" ]]; then
    "$PERL_BIN" -c "$MENU_CGI" >/dev/null
fi

menu_first="$(head -1 "$MENU_CGI")"
if [[ "$menu_first" != "#!/usr/local/cpanel/3rdparty/bin/perl" ]]; then
    echo "ERREUR : shebang Perl invalide : $menu_first" >&2
    exit 1
fi

if ! grep -q '^#WHMADDON:megastats:' "$MENU_CGI"; then
    echo "ERREUR : WHMADDON absent dans $MENU_CGI" >&2
    exit 1
fi

app_first="$(head -1 "$APP_CGI")"
if [[ "$app_first" != "#!/usr/local/cpanel/3rdparty/bin/php-cgi" ]]; then
    echo "ERREUR : shebang PHP invalide : $app_first" >&2
    exit 1
fi

echo "OK : $MENU_CGI (Perl WHMADDON → megastats/index.cgi)"
echo "OK : $APP_CGI (PHP-CGI AppConfig)"
