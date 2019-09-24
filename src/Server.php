<?php

namespace CalDAV;

class Server {

  public static function run() {
    $pdo = new \PDO('sqlite:db.sqlite');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $storageBackend = new \Sabre\DAV\PropertyStorage\Backend\PDO($pdo);
    $calendarBackend = new \Sabre\CalDAV\Backend\PDO($pdo);
    $principalBackend = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);

    $authBackend = new \Sabre\DAV\Auth\Backend\PDO($pdo);
    $authBackend->setRealm('SabreDAV');

    $server = new \Sabre\DAV\Server([
      new \Sabre\CalDAV\Principal\Collection($principalBackend),
      new \Sabre\CalDAV\CalendarRoot($principalBackend, $calendarBackend),
    ]);
    $server->setBaseUri('/calendar');

    $authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);
    $server->addPlugin($authPlugin);

    $aclPlugin = new \Sabre\DAVACL\Plugin();
    $server->addPlugin($aclPlugin);

    $caldavPlugin = new \Sabre\CalDAV\Plugin();
    $server->addPlugin($caldavPlugin);

    $propPlugin = new \Sabre\DAV\PropertyStorage\Plugin($storageBackend);
    $server->addPlugin($propPlugin);

    $syncPlugin = new \Sabre\DAV\Sync\Plugin();
    $server->addPlugin($syncPlugin);

    $browserPlugin = new \Sabre\DAV\Browser\Plugin();
    $server->addPlugin($browserPlugin);

    $setupPlugin = new SetupPlugin($pdo);
    $server->addPlugin($setupPlugin);

    $logPlugin = new LogPlugin();
    $server->addPlugin($logPlugin);

    $server->exec();
  }
}

?>
