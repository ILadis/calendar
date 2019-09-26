<?php

namespace CalDAV\InitSchema;

use CalDAV\ConsoleLogger;
use CalDAV\InitSchema;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $backend = null;
  private $logger = null;

  public function __construct(InitSchema\Backend\BackendInterface $backend) {
    $this->backend = $backend;
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $server->on('beforeMethod:*', [$this, 'initSchema'], 0);
  }

  public function initSchema(): void {
    if (!$this->backend->hasSchema()) {
      $this->logger->info('Creating database schema');
      $this->backend->createSchema();
      $this->logger->info('Successfully created database schema');
    }
  }
}

?>
