<?php

namespace CalDAV\Task;

use CalDAV\ConsoleLogger;

use Sabre\CalDAV;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;
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
    $successful = $this->runTask($name, $params);
    if (!$successful) {
      $response->setStatus(500);
      return false;
    }

    $response->setStatus(200);
    return false;
  }

  // TODO consider separate class for better testing
  private function runTask(string $name, array $params): bool {
    $task = $this->findTask($name);
    if (!$task) {
      return false;
    }

    $calendarId = $this->findCalendarId($name);
    if (!$calendarId) {
      return false;
    }

    $events = $task->run($params);
    if (!$events) {
      return false;
    }

    foreach ($events as $event) {
      $this->saveEvent($calendarId, $event);
    }

    return true;
  }

  private function findTask(string $name): ?Task {
    $name = ucfirst($name);
    $cls = "\\CalDAV\\Task\\{$name}Task";
    return new $cls();
  }

  private function findCalendarId(string $uri): ?array {
    $principal = $this->auth->getCurrentPrincipal();
    $calendars = $this->backend->getCalendarsForUser($principal);

    foreach ($calendars as $calendar) {
      if ($calendar['uri'] == $uri) {
        return $calendar['id'];
      }
    }

    return null;
  }

  private function saveEvent(array $calendarId, VCalendar $event): void {
    $uid = strval($event->VEVENT->UID);
    $uri = "{$uid}.ics";

    $object = $this->backend->getCalendarObject($calendarId, $uri);
    $data = $event->serialize();

    if (!$object) {
      $this->logger->info("Creating event '{$uid}'");
      $this->backend->createCalendarObject($calendarId, $uri, $data);
    }

    else if ($this->shouldUpdate($object, $event)) {
      $this->logger->info("Updating event '{$uid}'");
      $this->backend->updateCalendarObject($calendarId, $uri, $data);
    }
  }

  private function shouldUpdate(array $object, VCalendar $event): bool {
    $other = VObject\Reader::read($object['calendardata']);

    foreach ($event->VEVENT->children() as $child) {
      $key = strval($child->name);

      if ($key == 'DTSTAMP') {
        continue;
      }

      $value1 = strval($event->VEVENT->$key);
      $value2 = strval($other->VEVENT->$key);

      if ($value1 != $value2) {
        return true;
      }
    }

    return false;
  }
}

?>
