#!/usr/bin/env php
<?php

$readonly = boolval(ini_get('phar.readonly'));
if ($readonly) {
  echo 'Cannot build Phar when readonly flag is set!';
  exit(1);
}

$phar = new Phar('calendar.phar');
$phar->setDefaultStub('index.php');

$phar->buildFromDirectory(__DIR__, '/vendor/');
$phar->buildFromDirectory(__DIR__, '/src/');

$phar->addFromString('index.php', <<<EOF
<?php
require_once __DIR__ . '/vendor/autoload.php';
CalDAV\Server::run();
?>
EOF);

?>
