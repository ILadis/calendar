#!/usr/bin/env php
<?php

$username = $argc > 1 ? $argv[1] : 'user';
$password = $argc > 2 ? $argv[2] : 'secret';
$realm = $argc > 3 ? $argv[3] : 'SabreDAV';

$hash = md5("{$username}:{$realm}:{$password}");
$digest = "{$username}:{$realm}:{$hash}\n";

file_put_contents('users.htdigest', $digest, FILE_APPEND);

?>
