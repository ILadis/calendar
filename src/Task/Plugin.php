<?php

namespace CalDAV\Task;

use CalDAV\Calendar;
use CalDAV\ConsoleLogger;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $backend = null;
  private $logger = null;
  private $auth = null;

  public function __construct(Calendar\Backend\BackendInterface $backend) {
    $this->backend = $backend;
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $this->auth = $server->getPlugin('auth');
    $server->on('method:*', [$this, 'handleRequest']);
  }

  public function handleRequest(RequestInterface $request, ResponseInterface $response): bool {
    $path = $request->getPath();
    $match = preg_match('|^tasks/([a-z]+)/?$|', $path, $matches);
    if (!$match) {
      return true;
    }

    $principal = $this->auth->getCurrentPrincipal();
    if ($principal == null) {
      $this->logger->warning('Attempt to run task unauthenticated');
      $response->setStatus(401);
      return false;
    }

    $method = $request->getMethod();
    if ($method != 'POST') {
      $response->setStatus(405);
      $response->setHeader('Allow', 'POST');
      return false;
    }

    $body = $request->getBodyAsString();
    $params = json_decode($body, true);
    if (!is_array($params)) {
      $this->logger->warning('Attempt to run task without params');
      $response->setStatus(415);
      return false;
    }

    $name = $matches[1];
    $successful = $this->runTask($name, $principal, $params);
    if (!$successful) {
      $response->setStatus(500);
      return false;
    }

    $response->setStatus(200);
    return false;
  }

  private function runTask(string $name, string $principal, array $params): bool {
    $task = $this->findTask($name);
    if (!$task) {
      return false;
    }

    return $task->run($principal, $params);
  }

  private function findTask(string $name): ?Task {
    $name = ucfirst($name);
    $cls = "\\CalDAV\\Task\\{$name}Task";
    return new $cls($this->backend);
  }
}

?>
