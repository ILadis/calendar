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

  public function __construct(CalDAV\Backend\BackendInterface $backend) {
    $this->backend = $backend;
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
    $principal = $this->auth->getCurrentPrincipal();
    if ($principal == null) {
      $this->logger->warning('Attempt to refresh holidays unauthenticated');
      $response->setStatus(401);
      return false;
    }

    $calendar = $this->findCalendar($principal);
    if (!$calendar) {
      $this->logger->warning("User {$principal} does not own a suitable holidays calendar");
      $response->setStatus(404);
      return false;
    }

    $body = $request->getBodyAsString();
    $filter = $this->parseFilter($body);
    if (!$filter) {
      $response->setStatus(422);
      return false;
    }

    list($year, $state) = $filter;
    $this->logger->info("Refreshing {$state} holidays for {$year}");

    $url = "https://feiertage-api.de/api/?jahr={$year}&nur_land={$state}";
    $json = $this->fetchResource($url);

    if (!$json) {
      $this->logger->warning('Failed to query holidays service');
      $response->setStatus(503);
      return false;
    }

    foreach ($json as $title => $details) {
      $event = $this->createEvent($title, $details);

      if ($event) {
        list($uri, $event) = $event;
        $this->saveEvent($calendar, $uri, $event);
      }
    }

    $response->setStatus(200);
    return true;
  }

  private function findCalendar(string $principal) {
    $calendars = $this->backend->getCalendarsForUser($principal);

    foreach ($calendars as $calendar) {
      $id = $calendar['id'];
      $uri = $calendar['uri'];

      if ($uri == 'holidays') {
        return $id;
      }
    }

    return false;
  }

  private function parseFilter(string $body) {
    parse_str($body, $filter);

    $year = strval($filter['year'] ?? '');
    if (!preg_match('/^[0-9]{4}$/', $year)) {
      return false;
    }

    $state = strval($filter['state'] ?? '');
    if (!preg_match('/^[A-Z]{2}$/', $state)) {
      return false;
    }

    return [$year, $state];
  }

  private function fetchResource(string $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($output, true);
    if (!is_array($json)) {
      return false;
    }

    return $json;
  }

  private function createEvent(string $title, array $details) {
    $calendar = new VCalendar();

    $date = strval($details['datum'] ?? '');
    $hint = strval($details['hinweis'] ?? '');

    $date = \DateTime::createFromFormat('!Y-m-d', $date);
    if (!$date) {
      return false;
    }

    $date = $date->format('Ymd');

    $uid = sha1("{$title}{$date}");
    $uri = "{$uid}.ics";

    $event = $calendar->add('VEVENT', [
      'UID' => $uid,
      'SUMMARY' => $title,
      'DESCRIPTION' => $hint
    ]);

    $start = $event->add('DTSTART', $date);
    $start->add('VALUE', 'DATE');

    return [$uri, $calendar->serialize()];
  }

  private function saveEvent(array $calendar, string $uri, string $event) {
    $object = $this->backend->getCalendarObject($calendar, $uri);
    if ($object == null) {
      $this->backend->createCalendarObject($calendar, $uri, $event);
    } else {
      $this->backend->updateCalendarObject($calendar, $uri, $event);
    }
  }
}

?>
