<?php

namespace CalDAV;

use PDO;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SetupPlugin extends ServerPlugin {

  private $pdo = null;
  private $logger = null;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
    $this->logger = ConsoleLogger::for(SetupPlugin::class);
  }

  public function initialize(Server $server) {
    $server->on('beforeMethod:*', [$this, 'doSetup'], 0);
  }

  public function doSetup(RequestInterface $request, ResponseInterface $response) {
    if ($this->hasNoSchema()) {
      $this->logger->info('Creating sqlite schema');
      $this->createSchema();
      $this->logger->info('Successfully created sqlite schema');
    }
  }
  
  private function hasNoSchema() {
    $query = 'SELECT count(*) AS count FROM sqlite_master WHERE type = ?';
    $stmt = $this->pdo->prepare($query);
    $stmt->execute(['table']);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];

    return $count == 0;
  }

  private function createSchema() {
    $query = file_get_contents(__DIR__ . '/sql/schema.sql');
    $this->pdo->exec($query);
  }
}

?>
