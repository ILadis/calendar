<?php

namespace CalDAV\Holiday;

use CalDAV\ConsoleLogger;

use Sabre\VObject;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Exception\Forbidden;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $auth = null;
  private $logger = null;

  // TODO use Sabre\CalDAV\Backend to access calendar storage
  public function __construct() {
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $this->auth = $server->getPlugin('auth');
    $server->on('method:*', [$this, 'handleRequest']);
  }

  public function handleRequest(RequestInterface $request, ResponseInterface $response): bool {
    $path = $request->getPath();
    if ($path != 'holidays') {
      return true;
    }

    $principal = $this->auth->getCurrentPrincipal();
    if ($principal == null) {
      $this->logger->warn('Attempt to refresh holidays unauthenticated');

      $response->setStatus(403);
      return false;
    }

    $method = $request->getMethod();
    if ($method != 'POST') {
      $response->setStatus(405);
      $response->setHeader('Allow', 'POST');
      return false;
    }

    // TODO check mime type to match text/plain

    // TODO allow only current and next 5 years
    $body = $request->getBodyAsString();
    if (!preg_match('/^[0-9]*$/', $body)) {
      $this->logger->error('Body does not contain a valid year');

      $response->setStatus(422);
      return false;
    }

    $year = intval($body);
    $this->refreshEvents($year);

    $response->setStatus(200);
    return false;
  }

  private function refreshEvents(int $year): void {
    $this->logger->info("Refreshing bavarian holidays for {$year}");

    $url = "https://feiertage-api.de/api/?jahr={$year}&nur_land=BY";
    $response = $this->fetchResource($url);

    // TODO parse JSON and update calendar
  }

  private function fetchResource(string $url): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }
}

?>
