#!/usr/bin/env php
<?php

$phar = new Phar('calendar.phar');

$phar->buildFromDirectory(__DIR__, '/vendor/');
$phar->addFile('index.php');
$phar->addFromString('buildinfo.php', '<?php $PWD = "' . __DIR__ . '"; ?>');

$phar->setDefaultStub('index.php');

?>
