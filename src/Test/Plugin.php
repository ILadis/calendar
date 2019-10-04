<?php

namespace CalDAV\Test;

use CalDAV\ConsoleLogger;

use Sabre\VObject;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $auth = null;
  private $tree = null;
  private $logger = null;

  public function __construct() {
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $this->auth = $server->getPlugin('auth');
    $this->tree = $server->tree;
    $server->on('method:*', [$this, 'test']);
  }

  public function test(RequestInterface $request, ResponseInterface $response): bool {
    if ($request->getMethod() == 'POST' && $request->getPath() == 'some/path') {
      $this->logger->info("this is ours!");
      $response->setStatus(200);
      $response->setHeader('Content-Type', 'text/plain');
      $response->setBody('hello world!');
      return false;
    }

    $cls = get_class($this->tree);
    $this->logger->info("tree is: {$cls}");

    $principal = $this->auth->getCurrentPrincipal();
    $this->logger->info("curr principal is: {$principal}");

    $calRoot = $this->tree->getNodeForPath('calendars');
    $calHome = $calRoot->getChildForPrincipal(['uri' => 'principals/ladis']);

    if (!$calHome) {
      return true;
    }

    $exists = $calHome->childExists('events');

    $this->logger->info("events exists in cal home? {$exists}");

    $vcalendar = new VObject\Component\VCalendar([
      'VEVENT' => [
        //DESCRIPTION,LOCATION,SEQUENCE
        'SUMMARY' => 'Birthday party!',
        'DTSTART' => new \DateTime('2016-07-04 21:00:00'),
        'DTEND'   => new \DateTime('2016-07-05 03:00:00')
      ]
    ]);

    $cal = $vcalendar->serialize();
    $vcalendar->destroy();

    $this->logger->info("event is: {$cal}");


    // CalendarBackend
    // createCalendarObject($calendarId = 1, $objectUri = generate UUID or use first VEVENT UID, $calendarData)

    // -> can only contain one event...
    return true;
  }
}

?>
