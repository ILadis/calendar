<?php

namespace CalDAV\EventLogger;

use CalDAV\ConsoleLogger;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $logger = null;

  public function __construct() {
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $server->on('method:*', [$this, 'logRequest']);
  }

  public function logRequest(RequestInterface $request): void {
    $base = $request->getBaseUrl();
    $path = $request->getPath();
    $method = $request->getMethod();

    $this->logger->info("{$method} {$base}{$path}");
  }
}

?>
