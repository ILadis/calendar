<?php

namespace CalDAV\Setup;

use CalDAV\Log\ConsoleLogger;
use CalDAV\Setup\Backend\BackendInterface;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SetupPlugin extends ServerPlugin {

  private $backend = null;
  private $logger = null;

  public function __construct(BackendInterface $backend) {
    $this->backend = $backend;
    $this->logger = ConsoleLogger::for(SetupPlugin::class);
  }

  public function initialize(Server $server) {
    $server->on('beforeMethod:*', [$this, 'doSetup'], 0);
  }

  public function doSetup(RequestInterface $request, ResponseInterface $response) {
    if (!$this->backend->hasSchema()) {
      $this->logger->info('Creating sqlite schema');
      $this->backend->createSchema();
      $this->logger->info('Successfully created sqlite schema');
    }
  }
}

?>
