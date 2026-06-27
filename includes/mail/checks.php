<?php

declare(strict_types=1);

function ms_mail_rbl_zones(): array
{
    return [
        'zen.spamhaus.org' => 'Spamhaus',
        'bl.spamcop.net' => 'Spamcop',
        'b.barracudacentral.org' => 'Barracuda',
        'dnsbl.sorbs.net' => 'SORBS',
        'dnsbl-1.uceprotect.net' => 'UCEProtect',
        'psbl.surriel.com' => 'PSBL',
        'bl.mailspike.net' => 'Mailspike',
        'combined.rbl.msrbl.net' => 'MSRBL',
        'ix.dnsbl.manitu.net' => 'Manitu',
        'rbl.interserver.net' => 'InterServer',
        'dyna.spamrats.com' => 'SpamRATS Dyna',
        'noptr.spamrats.com' => 'SpamRATS NoPTR',
        'spam.spamrats.com' => 'SpamRATS Spam',
        'all.spamrats.com' => 'SpamRATS All',
        'truncate.gbudb.net' => 'GBUDB',
        'dnsbl.dronebl.org' => 'DroneBL',
        'black.junkemailfilter.com' => 'JunkEmailFilter',
        'hostkarma.junkemailfilter.com' => 'HostKarma',
        'dnsbl.justspam.org' => 'JustSpam',
        'bl.spameatingmonkey.net' => 'Spam Eating Monkey',
        'dnsbl.zapbl.net' => 'ZapBL',
        'multi.surbl.org' => 'SURBL',
        'rhsbl.sorbs.net' => 'SORBS RHSBL',
        'spam.dnsbl.anonmails.de' => 'Anonmails',
        'bl.scientificspam.net' => 'Scientific Spam',
        'korea.services.net' => 'Korea Services',
        'relays.nether.net' => 'Nether Relays',
        'access.redhawk.org' => 'Redhawk',
        'dnsbl.inps.de' => 'INPS',
        'bl.blocklist.de' => 'Blocklist.de',
    ];
}

function ms_mail_status(bool $ok, ?string $detail = null): array
{
    return ['ok' => $ok, 'detail' => $detail ?? ''];
}

function ms_mail_detect_ips(array $config): array
{
    $configured = $config['mail_sending_ips'] ?? [];
    if (is_array($configured) && $configured !== []) {
        return array_values(array_filter(array_map('trim', $configured)));
    }

    $out = ms_shell("hostname -I 2>/dev/null | awk '{print $1}'");
    if ($out !== '' && filter_var($out, FILTER_VALIDATE_IP)) {
        return [$out];
    }

    $host = ms_shell('hostname -f 2>/dev/null') ?: ms_shell('hostname 2>/dev/null');
    if ($host !== '') {
        $ip = gethostbyname($host);
        if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
            return [$ip];
        }
    }

    return [];
}

function ms_mail_detect_domains(array $config): array
{
    $configured = $config['mail_domains'] ?? [];
    if (is_array($configured) && $configured !== []) {
        return array_values(array_filter(array_map('trim', $configured)));
    }

    $host = ms_shell('hostname -f 2>/dev/null') ?: ms_shell('hostname 2>/dev/null');
    if ($host !== '' && str_contains($host, '.')) {
        return [$host];
    }

    if (is_file('/etc/localdomains')) {
        $lines = file('/etc/localdomains', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_slice(array_values(array_filter(array_map('trim', $lines))), 0, 3);
    }

    return $host !== '' ? [$host] : [];
}

function ms_mail_dns_txt(string $name): array
{
    $records = @dns_get_record($name, DNS_TXT);
    if (!is_array($records)) {
        return [];
    }

    $txt = [];
    foreach ($records as $rec) {
        if (isset($rec['txt'])) {
            $txt[] = (string) $rec['txt'];
        }
    }

    return $txt;
}

function ms_mail_check_spf(string $domain): array
{
    $txts = ms_mail_dns_txt($domain);
    foreach ($txts as $t) {
        if (stripos($t, 'v=spf1') === 0) {
            return ms_mail_status(true, $t);
        }
    }

    return ms_mail_status(false, 'Aucun enregistrement SPF (v=spf1) trouvé');
}

function ms_mail_check_dkim(string $domain, array $selectors): array
{
    foreach ($selectors as $sel) {
        $name = $sel . '._domainkey.' . $domain;
        $txts = ms_mail_dns_txt($name);
        foreach ($txts as $t) {
            if (stripos($t, 'v=DKIM1') !== false || stripos($t, 'p=') !== false) {
                return ms_mail_status(true, $name . ' : ' . mb_substr($t, 0, 80) . '…');
            }
        }
    }

    return ms_mail_status(false, 'DKIM introuvable (sélecteurs testés : ' . implode(', ', $selectors) . ')');
}

function ms_mail_check_dmarc(string $domain): array
{
    $txts = ms_mail_dns_txt('_dmarc.' . $domain);
    foreach ($txts as $t) {
        if (stripos($t, 'v=DMARC1') === 0) {
            return ms_mail_status(true, $t);
        }
    }

    return ms_mail_status(false, 'Aucun enregistrement DMARC (_dmarc.' . $domain . ')');
}

function ms_mail_check_ptr(string $ip, string $expectedHost): array
{
    $ptr = @gethostbyaddr($ip);
    if ($ptr === false || $ptr === $ip || $ptr === '') {
        return ms_mail_status(false, 'PTR absent pour ' . $ip);
    }

    $expectedHost = strtolower(rtrim($expectedHost, '.'));
    $ptrLower = strtolower(rtrim($ptr, '.'));
    $ok = $ptrLower === $expectedHost
        || str_ends_with($ptrLower, '.' . $expectedHost)
        || str_ends_with($expectedHost, '.' . $ptrLower);

    return ms_mail_status($ok, $ptr . ($ok ? '' : ' (attendu ~ ' . $expectedHost . ')'));
}

function ms_mail_check_rbl(string $ip): array
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return ['listed' => [], 'clean' => [], 'all' => []];
    }

    $rev = implode('.', array_reverse(explode('.', $ip)));
    $listed = [];
    $clean = [];

    foreach (ms_mail_rbl_zones() as $zone => $label) {
        $query = $rev . '.' . $zone . '.';
        $listedOnZone = false;

        if (@checkdnsrr($query, 'A') || @checkdnsrr($query, 'AAAA')) {
            $listedOnZone = true;
        }

        $entry = ['zone' => $zone, 'label' => $label, 'listed' => $listedOnZone];
        if ($listedOnZone) {
            $listed[] = $entry;
        } else {
            $clean[] = $entry;
        }
    }

    return [
        'listed' => $listed,
        'clean' => $clean,
        'all' => array_merge($listed, $clean),
    ];
}

function ms_mail_smtp_probe(string $host, int $port, string $helo): array
{
    $result = [
        'banner' => ms_mail_status(false),
        'helo' => ms_mail_status(false),
        'tls' => ms_mail_status(false),
    ];

    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, 8);
    if (!$fp) {
        $msg = "Connexion impossible : {$errstr} ({$errno})";
        $result['banner'] = ms_mail_status(false, $msg);
        $result['helo'] = ms_mail_status(false, $msg);
        $result['tls'] = ms_mail_status(false, $msg);
        return $result;
    }

    stream_set_timeout($fp, 8);
    $banner = fgets($fp, 512);
    if (is_string($banner) && str_starts_with($banner, '220')) {
        $result['banner'] = ms_mail_status(true, trim($banner));
    } else {
        $result['banner'] = ms_mail_status(false, trim((string) $banner));
    }

    $heloName = $helo !== '' ? $helo : 'megastats.local';
    fwrite($fp, "EHLO {$heloName}\r\n");
    $ehloResp = '';
    while (($line = fgets($fp, 512)) !== false) {
        $ehloResp .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    $ehloOk = str_contains($ehloResp, '250');
    $result['helo'] = ms_mail_status($ehloOk, trim($ehloResp));

    $tlsOffered = stripos($ehloResp, 'STARTTLS') !== false;
    if (!$tlsOffered) {
        $result['tls'] = ms_mail_status(false, 'STARTTLS non proposé');
        fclose($fp);
        return $result;
    }

    fwrite($fp, "STARTTLS\r\n");
    $tlsResp = fgets($fp, 512);
    if (!is_string($tlsResp) || !str_starts_with($tlsResp, '220')) {
        $result['tls'] = ms_mail_status(false, trim((string) $tlsResp));
        fclose($fp);
        return $result;
    }

    $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $result['tls'] = ms_mail_status($crypto === true, $crypto === true ? 'TLS négocié' : 'Échec négociation TLS');

    fclose($fp);
    return $result;
}

function ms_mail_check_spamassassin(): array
{
    $bin = ms_shell('command -v spamassassin 2>/dev/null');
    if ($bin === '') {
        return ['ok' => false, 'score' => null, 'max' => 10, 'detail' => 'spamassassin non installé'];
    }

    $tmp = tempnam(sys_get_temp_dir(), 'ms_sa_');
    if ($tmp === false) {
        return ['ok' => false, 'score' => null, 'max' => 10, 'detail' => 'Erreur temp file'];
    }

    $message = "Subject: MegaStats deliverability test\r\n\r\nTest message for SpamAssassin scoring.\r\n";
    file_put_contents($tmp, $message);

    $out = ms_shell($bin . ' -t < ' . escapeshellarg($tmp) . ' 2>&1');
    @unlink($tmp);

    if (preg_match('/score=(-?\d+(?:\.\d+)?)/i', $out, $m)) {
        $score = (float) $m[1];
        return [
            'ok' => true,
            'score' => $score,
            'max' => 10,
            'detail' => 'score=' . $score,
        ];
    }

    return ['ok' => false, 'score' => null, 'max' => 10, 'detail' => 'Score non détecté'];
}

function ms_mail_test_mx(string $host, int $port = 25): array
{
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, 8);
    if (!$fp) {
        return ms_mail_status(false, "{$host}: {$errstr}");
    }

    $banner = fgets($fp, 512);
    fclose($fp);

    return ms_mail_status(
        is_string($banner) && str_starts_with($banner, '220'),
        trim((string) $banner) ?: $host
    );
}

function ms_mail_check_expirations(string $domain, array $dnsChecks): array
{
    $items = [];

    $host = ms_mail_detect_domains([])[0] ?? $domain;
    $certHost = $host;
    $out = ms_shell('openssl s_client -connect ' . escapeshellarg($certHost . ':443') . ' -servername ' . escapeshellarg($certHost) . ' </dev/null 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null');
    if (preg_match('/notAfter=(.+)/', $out, $m)) {
        $exp = strtotime(trim($m[1]));
        $items['ssl'] = [
            'label' => 'SSL',
            'expires' => $exp ? date('Y-m-d', $exp) : trim($m[1]),
            'days' => $exp ? (int) floor(($exp - time()) / 86400) : null,
            'ok' => $exp === false || $exp > time() + 86400 * 14,
        ];
    } else {
        $items['ssl'] = ['label' => 'SSL', 'expires' => '—', 'days' => null, 'ok' => false];
    }

    $items['dkim'] = [
        'label' => 'DKIM',
        'expires' => ($dnsChecks['dkim']['ok'] ?? false) ? 'DNS OK' : 'À vérifier',
        'days' => null,
        'ok' => (bool) ($dnsChecks['dkim']['ok'] ?? false),
    ];
    $items['dmarc'] = [
        'label' => 'DMARC',
        'expires' => ($dnsChecks['dmarc']['ok'] ?? false) ? 'Politique publiée' : 'Manquant',
        'days' => null,
        'ok' => (bool) ($dnsChecks['dmarc']['ok'] ?? false),
    ];
    $items['ptr'] = [
        'label' => 'PTR',
        'expires' => ($dnsChecks['ptr']['ok'] ?? false) ? 'OK' : 'Incorrect',
        'days' => null,
        'ok' => (bool) ($dnsChecks['ptr']['ok'] ?? false),
    ];

    return $items;
}

function ms_mail_check_microsoft_snds(array $config, string $ip): array
{
    $key = trim((string) ($config['mail_snds_key'] ?? ''));
    if ($key === '') {
        return [
            'ok' => null,
            'level' => 'unknown',
            'label' => 'Non configuré',
            'detail' => 'Ajoutez mail_snds_key dans config/mail.php',
        ];
    }

    return [
        'ok' => true,
        'level' => 'good',
        'label' => 'SNDS configuré',
        'detail' => 'Consultez le portail SNDS pour le détail IP (API manuelle requise)',
    ];
}

function ms_mail_check_google_postmaster(string $domain): array
{
    return [
        'ok' => null,
        'level' => 'unknown',
        'label' => 'Postmaster Tools',
        'detail' => 'Vérifiez manuellement postmaster.google.com pour ' . $domain,
    ];
}

function ms_mail_check_yahoo(array $rblListed): array
{
    $yahooListed = false;
    foreach ($rblListed as $item) {
        if (stripos($item['label'], 'Spamhaus') !== false || stripos($item['zone'], 'spamhaus') !== false) {
            $yahooListed = $item['listed'];
        }
    }

    return ms_mail_status(!$yahooListed, $yahooListed ? 'Listé sur RBL majeure' : 'Aucune RBL majeure détectée');
}
