<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/buildinfo.php';

function exception_error_handler($errno, $errstr, $errfile, $errline) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('exception_error_handler');

Phar::mount('db.sqlite', "{$PWD}/db.sqlite");
Phar::mount('users.htdigest', "{$PWD}/users.htdigest");

$pdo = new PDO('sqlite:db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$storageBackend = new Sabre\DAV\PropertyStorage\Backend\PDO($pdo);
$calendarBackend = new Sabre\CalDAV\Backend\PDO($pdo);
$principalBackend = new Sabre\DAVACL\PrincipalBackend\PDO($pdo);

$authBackend = new Sabre\DAV\Auth\Backend\File('users.htdigest');
$authBackend->setRealm('SabreDAV');

$server = new Sabre\DAV\Server([
  new Sabre\CalDAV\Principal\Collection($principalBackend),
  new Sabre\CalDAV\CalendarRoot($principalBackend, $calendarBackend),
]);
$server->setBaseUri('/calendar');

$authPlugin = new Sabre\DAV\Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);

$aclPlugin = new Sabre\DAVACL\Plugin();
$server->addPlugin($aclPlugin);

$caldavPlugin = new Sabre\CalDAV\Plugin();
$server->addPlugin($caldavPlugin);

$propPlugin = new Sabre\DAV\PropertyStorage\Plugin($storageBackend);
$server->addPlugin($propPlugin);

$syncPlugin = new Sabre\DAV\Sync\Plugin();
$server->addPlugin($syncPlugin);

$browserPlugin = new Sabre\DAV\Browser\Plugin();
$server->addPlugin($browserPlugin);

$server->exec();
?>
