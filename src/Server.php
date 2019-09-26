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

    $authBackend = new \Sabre\DAV\Auth\Backend\PDO($pdo);
    $authBackend->setRealm('SabreDAV');
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

    $logPlugin = new \CalDAV\RequestLogger\Plugin();
    $server->addPlugin($logPlugin);

    $server->exec();
  }
}

?>
