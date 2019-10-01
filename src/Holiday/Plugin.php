<?php

namespace CalDAV\Holiday;

use CalDAV\ConsoleLogger;

use Sabre\CalDAV;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;

class Plugin extends ServerPlugin {

  private $backend = null;
  private $logger = null;
  private $auth = null;
  private $calendarId = 0;

  public function __construct(CalDAV\Backend\BackendInterface $backend) {
    $this->backend = $backend;
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function setCalendarId(int $id): void {
    $this->calendarId = $id;
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
      $this->logger->warning('Attempt to refresh holidays unauthenticated');
      $response->setStatus(403);
      return false;
    }

    $method = $request->getMethod();
    switch ($method) {
    case 'POST':
      $this->refreshEvents($request, $response);
      break;

    default:
      $response->setStatus(405);
      $response->setHeader('Allow', 'POST');
    }

    return false;
  }

  private function refreshEvents(RequestInterface $request, ResponseInterface $response): bool {
    $year = $this->parseYear($request);
    if ($year == -1) {
      $response->setStatus(422);
      return false;
    }

    $this->logger->info("Refreshing bavarian holidays for {$year}");

    $url = "https://feiertage-api.de/api/?jahr={$year}&nur_land=BY";
    $result = $this->fetchResource($url);

    $json = json_decode($result, true);
    if (!is_array($json)) {
      $response->setStatus(500);
      return false;
    }

    foreach ($json as $title => $details) {
      list($uri, $event) = $this->createEvent($title, $details);
      $calendarId = $this->calendarId;

      try {
        $this->backend->createCalendarObject([$calendarId, null], $uri, $event);
      } catch (\PDOException $e) {
        $this->logger->info("Event {$uri} does already exist");
      }
    }

    $response->setStatus(200);
    return true;
  }

  private function parseYear(RequestInterface $request): int {
    // TODO check mime type to match text/plain
    // TODO allow only current and next 5 years
    $body = $request->getBodyAsString();
    if (!preg_match('/^[0-9]*$/', $body)) {
      return -1;
    }

    $year = intval($body);
    return $year;
  }

  private function fetchResource(string $url): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }

  private function createEvent(string $title, array $details): array {
    $calendar = new VCalendar();

    // TODO validate title and details
    $date = $details['datum'];
    $hint = $details['hinweis'];

    $uid = sha1("{$title}{$date}");
    $uri = "{$uid}.ics";

    $calendar->add('VEVENT', [
      'UID' => $uid,
      'SUMMARY' => $title,
      'DTSTART' => new \DateTime($date),
      'DESCRIPTION' => $hint
    ]);

    return [$uri, $calendar->serialize()];
  }
}

?>
