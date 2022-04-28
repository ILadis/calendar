<?php

namespace CalDAV\Admin;

use CalDAV\ConsoleLogger;
use CalDAV\Admin;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\Auth\Basic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $admin = null;
  private $logger = null;
  private $auth = null;

  public function __construct(Admin\Backend\BackendInterface $admin) {
    $this->admin = $admin;
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $this->auth = $server->getPlugin('auth');
    $server->on('method:*', [$this, 'handleRequest']);
  }

  public function handleRequest(RequestInterface $request, ResponseInterface $response): bool {
    // TODO create router helper class for this
    $path = $request->getPath();
    $match = preg_match('|^todos/?$|', $path);
    if (!$match) {
      return true;
    }

    $principal = $this->auth->getCurrentPrincipal();
    if ($principal == null) {
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
      $response->setStatus(415);
      return false;
    }

    $principals = $params['principals'];
    $title = $params['title'];
    $uri = $params['uri'];

    $result = $this->admin->newSharedTodoList($principals, $title, $uri);

    $response->setStatus($result ? 201 : 400);
    return false;
  }
}

?>
