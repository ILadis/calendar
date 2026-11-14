<?php

namespace CalDAV\Calendar\Backend;

use Sabre\VObject\Component\VCalendar;

interface BackendInterface {
  public function findCalendarId(string $principal, string $uri): ?array;
  public function saveEvent(array $calendarId, VCalendar $event): void;

  public function newSharedTodoList(array $principals, string $displayName, string $uri): bool;
}

?>
