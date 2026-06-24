<?php

declare(strict_types=1);

function ms_render_plain(string $content, string $margin = '10px 0 0 4px'): void
{
    ms_security_headers();
    echo "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Output</title></head>";
    echo "<body style=\"margin:{$margin};padding:0;background:#111;color:#ddd;\">";
    echo "<pre style=\"font-family:Consolas,monospace;font-size:12px;line-height:1.4;margin:0;padding:8px;\">{$content}</pre>";
    echo '</body></html>';
}

function ms_render_popup(array $config, string $cmd, string $out, string $meta, string $shorttime, string $buttons, string $title): void
{
    ms_security_headers();
    ms_render_template('popup', [
        'cmd' => $cmd,
        'out' => $out,
        'meta' => $meta,
        'shorttime' => $shorttime,
        'buttons_html' => $buttons,
        'title' => $title,
        'assets_base' => $config['assets_base'],
    ]);
}

function ms_handle_request(array $config): bool
{
    $scriptname = $config['scriptname'];
    $shorttime = $config['shorttime'];
    $allowed = $config['allowed_cmds'] ?? [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ms_post('cleartmp', '') === '1') {
        if (!ms_can_clear_tmp($config)) {
            ms_log($config, 'auth', 'Clear /tmp denied');
            header('Location: ' . ms_url($scriptname, ['cleartmp' => 'denied']));
            exit;
        }

        if (!empty($config['csrf_enabled']) && !ms_verify_csrf($config)) {
            header('Location: ' . ms_url($scriptname, ['cleartmp' => 'csrf']));
            exit;
        }

        if ((string) ms_post('confirm', '') !== 'yes') {
            header('Location: ' . ms_url($scriptname, ['cleartmp' => 'confirm']));
            exit;
        }

        $result = ms_clear_tmp_directory($config);
        $flag = $result['ok'] ? 'ok' : 'partial';
        header('Location: ' . ms_url($scriptname, [
            'cleartmp' => $flag,
            'deleted' => (string) ($result['deleted'] ?? 0),
            'skipped' => (string) ($result['skipped'] ?? 0),
        ]));
        exit;
    }

    if (ms_get('traffic')) {
        ms_render_plain(ms_shell('vnstat -tr | grep --after-context=3 Traffic'));
        return true;
    }

    if (ms_get('showports')) {
        ms_render_plain(
            "Port   What Is It?\n----   -----------------------\n  21   FTP server\n  25   Exim - SMTP\n  53   Bind nameserver\n  80   Apache webserver\n 110   POP mail server\n 143   IMAP mail server\n 443   Secure Apache webserver\n 465   Secure SMTP\n 993   Secure IMAP\n2082   cPanel\n2083   Secure cPanel (https)\n2086   WHM\n2087   Secure WHM (https)\n2095   Webmail\n2096   Secure webmail (https)\n3306   MySQL\n8888   Secure shell - SSHD",
            '10px 0 0 30px'
        );
        return true;
    }

    if (ms_get('users')) {
        ms_render_plain("Logged-in Users\n---------------\n" . ms_shell('w'), '10px 0 0 6px');
        return true;
    }

    if (ms_get('connections')) {
        $raw = ms_shell((string) $config['netstat_com']);
        $stats = ms_count_connected_clients($raw);
        ms_render_plain(ms_format_connections_report($stats), '10px 0 0 6px');
        return true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ms_post('whois') !== null) {
        if (!ms_verify_csrf($config)) {
            ms_log($config, 'auth', 'CSRF failed on whois form');
            ms_render_plain('Invalid security token.');
            return true;
        }
    }

    $whois = trim((string) ms_post('whois', ''));
    if ($whois === '') {
        $whois = trim((string) ms_get('whois', ''));
    }

    if ($whois !== '') {
        $whois = ms_sanitize_whois($whois);
        ms_log($config, 'activity', 'Whois lookup: ' . $whois);
        ms_render_plain(ms_shell('whois ' . escapeshellarg($whois)), '10px 0 0 30px');
        return true;
    }

    if (ms_get('lsal')) {
        ms_render_plain("Command: ls -al /tmp\n\n" . ms_shell('ls -al /tmp'), '10px 0 0 6px');
        return true;
    }

    if (ms_get('psaux')) {
        $psout = "Command: ps -aux\n\n" . ms_shell('ps -aux');
        ms_render_plain(str_replace('<', '&lt;', $psout), '10px 0 0 6px');
        return true;
    }

    if (ms_get('psmem')) {
        $psout = "Command: ps -auxh --sort=size | tac\n\n";
        $psout .= "USER       PID %CPU %MEM   VSZ  RSS TTY      STAT START   TIME COMMAND\n";
        $psout .= ms_shell('ps -auxh --sort=size | tac');
        ms_render_plain(str_replace('<', '&lt;', $psout), '10px 0 0 6px');
        return true;
    }

    $cmd = ms_get('cmd');
    if (!is_string($cmd) || $cmd === '') {
        return false;
    }

    if (!in_array($cmd, $allowed, true)) {
        ms_log($config, 'error', 'Blocked unknown cmd: ' . $cmd);
        ms_render_plain('Unknown command.');
        return true;
    }

    $out = '';
    $meta = '';
    $buttons = '';
    $title = '';

    if ($cmd === 'top') {
        $out = ms_shell('top -n 1 -b');
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['top_refresh'] * 60) . '">';
    } elseif ($cmd === 'vpsstat') {
        [$out] = ms_vpsstat();
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['vpsstat_refresh'] * 60) . '">';
    } elseif ($cmd === 'netstat') {
        $out = ms_netstat($config['netstat_com'], $scriptname);
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['netstat_refresh'] * 60) . '">';
        $buttons = '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.replace(\'' . ms_e(ms_url($scriptname, ['cmd' => 'netstat2'])) . '\')">Listening</button>';
        $title = 'netstat -nt (TCP connections)';
    } elseif ($cmd === 'netstat2') {
        $out = ms_shell('netstat -ntl');
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['netstat_refresh'] * 60) . '">';
        $buttons = '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.replace(\'' . ms_e(ms_url($scriptname, ['cmd' => 'netstat'])) . '\')">Active</button>';
        $title = 'netstat -ntl (listening TCP ports)';
    } elseif ($cmd === 'mytop') {
        $out = ms_shell($config['mysql_com']);
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['mysql_refresh'] * 60) . '">';
    } elseif ($cmd === 'mysqlreport') {
        $out = str_replace('_', '-', ms_shell($config['mysql_com2']));
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['mysql_refresh'] * 60) . '">';
    } elseif ($cmd === 'vnstat') {
        $out = ms_shell('vnstat');
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['vnstat_refresh'] * 60) . '">';
    } elseif ($cmd === 'vnstat2') {
        $out = ms_shell('vnstat -d');
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['vnstat_refresh'] * 60) . '">';
        $title = 'vnstat -d';
    } elseif ($cmd === 'vnstat3') {
        $out = ms_shell('vnstat -m');
        $meta = '<meta http-equiv="refresh" content="' . ((int) $config['vnstat_refresh'] * 60) . '">';
        $title = 'vnstat -m';
    } elseif ($cmd === 'vnstat4') {
        $out = ms_shell('vnstat -tr | grep --after-context=3 Traffic');
        $title = 'vnstat -tr';
    }

    if (stripos($cmd, 'vnstat') !== false) {
        $buttons = '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.replace(\'' . ms_e(ms_url($scriptname, ['cmd' => 'vnstat4'])) . '\')">Sample</button> '
            . '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.replace(\'' . ms_e(ms_url($scriptname, ['cmd' => 'vnstat'])) . '\')">Today</button> '
            . '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.replace(\'' . ms_e(ms_url($scriptname, ['cmd' => 'vnstat2'])) . '\')">Days</button> '
            . '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.replace(\'' . ms_e(ms_url($scriptname, ['cmd' => 'vnstat3'])) . '\')">Months</button> '
            . '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.close()">Close</button>';
    } else {
        $buttons .= '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.location.reload()">Reload</button> '
            . '<button type="button" class="btn btn-sm btn-outline-light" onclick="window.close()">Close</button>';
    }

    if ($title === '') {
        $title = $cmd;
    }

    ms_log($config, 'activity', 'Popup command: ' . $cmd);
    ms_render_popup($config, $cmd, $out, $meta, $shorttime, $buttons, $title);
    return true;
}
