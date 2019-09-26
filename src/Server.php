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

    $authBackend = new \CalDAV\Users\Backend\PDO($pdo);
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

    $setupBackend = new \CalDAV\Setup\Backend\PDO($pdo);
    $setupPlugin = new \CalDAV\Setup\Plugin($setupBackend);
    $server->addPlugin($setupPlugin);

    $registerBackend = new \CalDAV\Registrations\Backend\PDO($pdo);
    $registerPlugin = new \CalDAV\Registrations\Plugin($registerBackend, $authBackend);
    $server->addPlugin($registerPlugin);

    $logPlugin = new \CalDAV\Logging\Plugin();
    $server->addPlugin($logPlugin);

    $server->exec();
  }
}

?>
