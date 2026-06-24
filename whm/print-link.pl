#!/usr/local/cpanel/3rdparty/bin/perl
use strict;
use warnings;

my $host = `hostname -f 2>/dev/null` || `hostname`;
chomp $host;

eval {
    require Cpanel::LogMeIn;
    my ($ok, $msg, $url) = Cpanel::LogMeIn::get_loggedin_url(
        'user'     => 'root',
        'pass'     => '',
        'hostname' => $host,
        'service'  => 'whostmgr',
        'goto_uri' => '/cgi/addon_megastats.cgi',
    );
    if ($ok && $url) {
        print "$url\n";
        exit 0;
    }
    print "LogMeIn failed: $msg\n";
    exit 1;
};

print "https://$host:2087/cgi/addon_megastats.cgi\n";
print "(Ouvrez cette URL depuis WHM deja connecte — copiez le cpsess depuis la barre d'adresse WHM)\n";
exit 0;
