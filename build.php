#!/usr/bin/env php
<?php

$readonly = boolval(ini_get('phar.readonly'));
if ($readonly) {
  echo 'Cannot build Phar when readonly flag is set!';
  exit(1);
}

$phar = new Phar('calendar.phar');

$phar->buildFromDirectory(__DIR__, '/vendor/');
$phar->addFile('index.php');
$phar->addFromString('buildinfo.php', '<?php $PWD = "' . __DIR__ . '"; ?>');

$phar->setDefaultStub('index.php');

?>
