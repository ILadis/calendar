<?php

namespace CalDAV\Task\Backend;

use CalDAV\ConsoleLogger;

use Sabre\CalDAV;
use Sabre\DAVACL;
use Sabre\VObject;
use Sabre\VObject\Component\VCalendar;

class PDO implements BackendInterface {

  private $pdo = null;
  private $logger = null;
  private $delegates = null;

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
    $this->logger = ConsoleLogger::for(PDO::class);
    $this->delegates = [
      'cal' => new CalDAV\Backend\PDO($pdo),
      'acl' => new DAVACL\PrincipalBackend\PDO($pdo),
    ];
  }

  public function findCalendarId(string $principal, string $uri): ?array {
    $calendars = $this->delegates['cal']->getCalendarsForUser($principal);

    foreach ($calendars as $calendar) {
      if ($calendar['uri'] == $uri) {
        return $calendar['id'];
      }
    }

    return null;
  }

  public function saveEvent(array $calendarId, VCalendar $event): void {
    $uid = strval($event->VEVENT->UID);
    $uri = "{$uid}.ics";

    $object = $this->delegates['cal']->getCalendarObject($calendarId, $uri);
    $data = $event->serialize();

    if (!$object) {
      $this->logger->info("Creating event '{$uid}'");
      $this->delegates['cal']->createCalendarObject($calendarId, $uri, $data);
    }

    else if ($this->shouldUpdate($object, $event)) {
      $this->logger->info("Updating event '{$uid}'");
      $this->delegates['cal']->updateCalendarObject($calendarId, $uri, $data);
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

  public function newSharedTodoList(array $principals, string $title, string $uri): bool {
    $calendarId = false;

    foreach ($principals as $principal) {
      $principalId = $this->findPrincipalId($principal);

      if ($principalId <= 0) {
        continue;
      }
      else if ($calendarId === false) {
        $stmt = $this->pdo->prepare('INSERT INTO calendars (components) VALUES ("VTODO")');
        $result = $stmt->execute();

        $calendarId = intval($this->pdo->lastInsertId());

        if (!$result || $calendarId <= 0) {
          return false;
        }
      }

      $stmt = $this->pdo->prepare('INSERT INTO calendarinstances (calendarid, principaluri, access, displayname, uri) VALUES (?, ?, ?, ?, ?)');
      $result = $stmt->execute([$calendarId, $principal, 3 /* shared */, $title, $uri]);

      if (!$result) {
        return false;
      }
    }

    return true;
  }

  private function findPrincipalId(string $path): int {
    $principal = $this->delegates['acl']->getPrincipalByPath($path);
    if (!$principal) {
      return -1;
    }

    return $principal['id'];
  }

  public function deletePrincipal(string $principal): bool {
    $principalId = $this->findPrincipalId($principal);
    if ($principalId <= 0) {
      return false;
    }

    $this->pdo->prepare('DELETE FROM groupmembers WHERE principal_id = ?')->execute([$principalId]);
    $this->pdo->prepare('DELETE FROM schedulingobjects WHERE principaluri = ?')->execute([$principal]);
    $this->pdo->prepare('DELETE FROM calendarsubscriptions WHERE principaluri = ?')->execute([$principal]);
    $this->pdo->prepare('DELETE FROM calendarinstances WHERE principaluri = ?')->execute([$principal]);
    $this->pdo->prepare('DELETE FROM principals WHERE uri = ?')->execute([$principal]);
    // TODO also remove from users by username

    $stmt = $this->pdo->query(''
        .'SELECT c.id FROM calendars c '
        .'LEFT JOIN calendarinstances ci ON ci.calendarid = c.id '
        .'WHERE ci.id IS NULL');

    $orphanedCalendarIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($orphanedCalendarIds as $calendarId) {
      $this->pdo->prepare('DELETE FROM calendarchanges WHERE calendarid = ?')->execute([$calendarId]);
      $this->pdo->prepare('DELETE FROM calendarobjects WHERE calendarid = ?')->execute([$calendarId]);
      $this->pdo->prepare('DELETE FROM calendars WHERE id = ?')->execute([$calendarId]);
    }

    return true;
  }
}

?>
