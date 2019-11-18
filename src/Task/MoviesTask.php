<?php

namespace CalDAV\Task;

use DateTime;

use Sabre\HTTP;
use Sabre\VObject\Component\VCalendar;

class MoviesTask implements Task {

  private $client;

  public function __construct($client = null) {
    $this->client = $client ?? new HTTP\Client();
  }

  public function run(array $params): ?array {
    $valid = $this->verifyParams($params, $from, $to);
    if (!$valid) {
      return null;
    }

    $json = $this->fetchResource($from, $to);
    if (!$json) {
      return null;
    }

    $events = array();
    foreach ($json as $movie) {
      $event = $this->createEvent($movie);

      if ($event) {
        array_push($events, $event);
      }
    }

    return $events;
  }

  private function verifyParams(array $params, &$from, &$to): bool {
    $month = strval($params['month'] ?? '');
    $date = DateTime::createFromFormat('!Y-m', $month);
    if (!$date) {
      return false;
    }

    $from = $date->format('Y-m-d\TH:i:s\Z');

    $date->modify('+1 month');
    $to = $date->format('Y-m-d\TH:i:s\Z');

    return true;
  }

  private function fetchResource(string $from, string $to): ?array {
    $url = 'https://deinkinoticket.de/api/v1/films'
      . '?$orderby=filmstart&$filter='
        . 'is_alternative_content+eq+false'
        . '+and+is_arthouse+eq+false'
        . "+and+filmstart+gt+{$from}"
        . "+and+filmstart+lt+{$to}";

    $location = urlencode('{"lon":11.07,"lat":49.45}');

    $request = new HTTP\Request('GET', $url);
    $request->setHeader('Cookie', "selectedLocation={$location}");

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

  private function createEvent(array $movie): ?VCalendar {
    $calendar = new VCalendar();

    $id = strval($movie['id'] ?? '');
    $title = strval($movie['title'] ?? '');
    $date = strval($movie['filmstart'] ?? '');
    $url = strval($movie['url'] ?? '');

    $date = DateTime::createFromFormat('!Y-m-d\TH:i:s\Z', $date);
    if (!$date) {
      return null;
    }

    $date = $date->format('Ymd');
    $uid = sha1("{$id}");

    if ($url) {
      $url = 'https://deinkinoticket.de/' . $url;
    }

    $event = $calendar->add('VEVENT', [
      'UID' => $uid,
      'SUMMARY' => $title,
      'DESCRIPTION' => $url
    ]);

    $start = $event->add('DTSTART', $date);
    $start->add('VALUE', 'DATE');

    return $calendar;
  }

}

?>
