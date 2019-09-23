<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/properties.php';

function exception_error_handler($errno, $errstr, $errfile, $errline) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('exception_error_handler');

Phar::mount('db.sqlite', Properties::PDO_PATH);

$pdo = new PDO('sqlite:db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

CalDAV\Server::run($pdo);

?>
