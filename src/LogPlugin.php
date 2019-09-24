<?php 
namespace CalDAV;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class LogPlugin extends ServerPlugin {

  private $logger = null;

  public function __construct() {
    $this->logger = ConsoleLogger::for(LogPlugin::class);
  }

  public function initialize(Server $server) {
    $server->on('method:*', [$this, 'doLogging']);
  }

  public function doLogging(RequestInterface $request, ResponseInterface $response) {
    $base = $request->getBaseUrl();
    $path = $request->getPath();
    $method = $request->getMethod();

    $this->logger->info("{$method} {$base}{$path}");
  }
}

?>
