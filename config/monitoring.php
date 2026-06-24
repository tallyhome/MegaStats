<?php

declare(strict_types=1);

return [
    // 0 = none; 1 = mytop; 2 = mysqlreport
    'mysql_mon' => 2,
    'vnstat' => 1,
    'my_db' => 'mysql',
    'my_user' => 'root',
    'userhome' => '/home/username',
    'processes' => 'ftpd mariadbd sshd http exim cpanel nginx php-fpm',
    'top_refresh' => 1,
    'vpsstat_refresh' => 1,
    'netstat_refresh' => 1,
    'mysql_refresh' => 1,
    'vnstat_refresh' => 1,
    'bw_alert' => 1000,
    'netstat_com' => 'netstat -nt',
    'vnstat_com' => 'vnstat',
    'top_com' => 'top -n 1 -b',
    'pstree_com' => 'env LANG=C pstree -c',
    'df_com' => 'df -h --exclude-type=tmpfs',
    'tmp_com' => 'ls -a --ignore=sess_* /tmp',
    'allps_com' => "ps -e | awk '{ print $4;}' | uniq",
    'allowed_cmds' => [
        'top', 'vpsstat', 'netstat', 'netstat2',
        'mytop', 'mysqlreport',
        'vnstat', 'vnstat2', 'vnstat3', 'vnstat4',
    ],
];
