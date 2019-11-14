<?php

namespace CalDAV\Task;

use DateTime;
use Sabre\VObject\Component\VCalendar;

class HolidaysTask implements Task {

  public function run(array $params): ?array {
    $valid = $this->verifyParams($params, $year, $state);
    if (!$valid) {
      return null;
    }

    $url = 'https://feiertage-api.de/api/'
      . "?jahr={$year}&nur_land={$state}";

    $json = $this->fetchResource($url);
    if (!$json) {
      return null;
    }

    $events = array();
    foreach ($json as $title => $details) {
      $event = $this->createEvent($title, $details);

      if ($event) {
        $events[] = $event;
      }
    }

    return $events;
  }

  private function verifyParams(array $params, &$year, &$state): bool {
    $year = strval($params['year'] ?? '');
    if (!preg_match('/^[0-9]{4}$/', $year)) {
      return false;
    }

    $state = strval($params['state'] ?? '');
    if (!preg_match('/^[A-Z]{2}$/', $state)) {
      return false;
    }

    return true;
  }

  private function fetchResource(string $url): ?array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($output, true);
    if (!is_array($json)) {
      return null;
    }

    return $json;
  }

  private function createEvent(string $title, array $details): ?VCalendar {
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
