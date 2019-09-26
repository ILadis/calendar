<?php

namespace CalDAV;

class Server {

  public static function run() {
    $pdo = new \PDO('sqlite:db.sqlite');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $principalBackend = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);
    $calendarBackend = new \Sabre\CalDAV\Backend\PDO($pdo);

    $server = new \Sabre\DAV\Server([
      new \Sabre\CalDAV\Principal\Collection($principalBackend),
      new \Sabre\CalDAV\CalendarRoot($principalBackend, $calendarBackend),
    ]);
    $server->setBaseUri('/calendar');

    $authBackend = new \CalDAV\BasicAuth\Backend\PDO($pdo);
    $authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);
    $server->addPlugin($authPlugin);

    $aclPlugin = new \Sabre\DAVACL\Plugin();
    $server->addPlugin($aclPlugin);

    $caldavPlugin = new \Sabre\CalDAV\Plugin();
    $server->addPlugin($caldavPlugin);

    $propBackend = new \Sabre\DAV\PropertyStorage\Backend\PDO($pdo);
    $propPlugin = new \Sabre\DAV\PropertyStorage\Plugin($propBackend);
    $server->addPlugin($propPlugin);

    $syncPlugin = new \Sabre\DAV\Sync\Plugin();
    $server->addPlugin($syncPlugin);

    $browserPlugin = new \Sabre\DAV\Browser\Plugin();
    $server->addPlugin($browserPlugin);

    $initBackend = new \CalDAV\InitSchema\Backend\PDO($pdo);
    $initPlugin = new \CalDAV\InitSchema\Plugin($initBackend);
    $server->addPlugin($initPlugin);

    $autoBackend = new \CalDAV\AutoUser\Backend\PDO($pdo);
    $autoPlugin = new \CalDAV\AutoUser\Plugin($autoBackend, $authBackend);
    $server->addPlugin($autoPlugin);

    $logPlugin = new \CalDAV\EventLogger\Plugin();
    $server->addPlugin($logPlugin);

    $server->exec();
  }
}

?>
