<?php

namespace CalDAV\Task;

use DateTime;
use CalDAV\Task\Backend;

use Sabre\HTTP;
use Sabre\VObject\Component\VCalendar;

class HolidaysTask extends Task {

  private $backend = null;
  private $client = null;

  public function __construct(Backend\BackendInterface $backend, ?HTTP\Client $client = null) {
    $this->backend = $backend;
    $this->client = $client ?? new HTTP\Client();
  }

  public function run(string $principal, array $params): bool {
    if (Task::param($params, 'year',  '/[0-9]{4}/', $year)  != 1 ||
        Task::param($params, 'state', '/[A-Z]{2}/', $state) != 1) {
      return false;
    }

    $calendarId = $this->backend->findCalendarId($principal, 'holidays');
    if (!$calendarId) {
      return false;
    }

    $json = $this->fetchResource(current($year), current($state));
    if (!$json) {
      return false;
    }

    $events = array();
    foreach ($json as $title => $details) {
      $event = $this->createEvent($title, $details);

      if ($event) {
        $events[] = $event;
      }
    }

    foreach ($events as $event) {
      $this->backend->saveEvent($calendarId, $event);
    }

    return true;
  }

  private function fetchResource(string $year, string $state): ?array {
    $url = 'https://feiertage-api.de/api/'
      . "?jahr={$year}&nur_land={$state}";

    $request = new HTTP\Request('GET', $url);
    $response = $this->client->send($request);

    $status = $response->getStatus();
    if ($status != 200) {
      return null;
    }

    $body = $response->getBodyAsString();
    $json = json_decode($body, true);

    if (!is_array($json)) {
      return null;
    }

    return $json;
  }

  private function createEvent($title, $details): ?VCalendar {
    if (!is_string($title) || !is_array($details)) {
      return null;
    }

    $calendar = new VCalendar();

    $date = strval($details['datum'] ?? '');
    $hint = strval($details['hinweis'] ?? '');

    $date = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$date) {
      return null;
    }

    $date = $date->format('Ymd');
    $uid = sha1("{$title}{$date}");

    $event = $calendar->add('VEVENT', [
      'UID' => $uid,
      'SUMMARY' => $title,
      'DESCRIPTION' => $hint
    ]);

    $start = $event->add('DTSTART', $date);
    $start->add('VALUE', 'DATE');

    return $calendar;
  }

}

?>
