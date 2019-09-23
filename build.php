#!/usr/bin/env php
<?php

$cwd = getcwd();
$readonly = boolval(ini_get('phar.readonly'));
if ($readonly) {
  echo 'Cannot build Phar when readonly flag is set!';
  exit(1);
}

$phar = new Phar('calendar.phar');

$phar->buildFromDirectory(__DIR__, '/vendor/');
$phar->buildFromDirectory(__DIR__, '/src/');

$phar->addFile('index.php');
$phar->addFromString('properties.php', <<<EOF
<?php

class Properties {
  const PDO_PATH = '$cwd/db.sqlite';
}

?>
EOF);

$phar->setDefaultStub('index.php');

?>
