#!/usr/local/bin/ea-php82
<?php

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = 'app.cgi' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 302);
exit;
