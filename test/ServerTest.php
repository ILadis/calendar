<?php

namespace CalDAV;

use PHPUnit\Framework\TestCase;
use Sabre\HTTP;
use CalDAV\ConsoleLogger;

class ServerTest extends TestCase {

  protected function setUp(): void {
    @unlink('temp.sqlite');

    $http = self::newExchange();
    Server::run('/', 'temp.sqlite', $http);

    // TODO silence consoler logger on first exchange/run
  }

  public function testShouldLoginExistingUser() {
    // arrange
    $authz = base64_encode('user1:dummypwd');
    $password = password_hash('dummypwd', PASSWORD_BCRYPT);

    $http = self::newExchange();
    $http->getRequest()->setUrl('/calendar/');
    $http->getRequest()->setHeader('Authorization', "Basic {$authz}");

    $db = new \SQLite3('temp.sqlite');
    $db->exec("INSERT INTO users (id, username, password) VALUES (1, 'user1', '{$password}')");
    $db->exec("INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1')");

    // act
    Server::run('/calendar', 'temp.sqlite', $http);

    // assert
    $this->assertEquals(200, $http->getResponse()->getStatus());
  }

  public function testShouldRegisterNewUser() {
    // arrange
    $authz = base64_encode('newuser:somepwd');

    $db = new \SQLite3('temp.sqlite');
    $db->exec("INSERT INTO registrations (username) VALUES ('newuser')");

    $http = self::newExchange();
    $http->getRequest()->setUrl('/calendar/');
    $http->getRequest()->setHeader('Authorization', "Basic {$authz}");

    // act
    Server::run('/calendar', 'temp.sqlite', $http);

    // assert
    $this->assertEquals(200, $http->getResponse()->getStatus());
    $this->assertEquals(1, $db->querySingle("SELECT count(*) FROM users WHERE username='newuser'"));
    $this->assertEquals(1, $db->querySingle("SELECT count(*) FROM principals WHERE uri='principals/newuser'"));
  }

  public function testShouldExecuteHolidaysTask() {
    // arrange
    $authz = base64_encode('user1:dummypwd');
    $password = password_hash('dummypwd', PASSWORD_BCRYPT);

    $db = new \SQLite3('temp.sqlite');
    $db->exec("INSERT INTO users (id, username, password) VALUES (1, 'user1', '{$password}')");
    $db->exec("INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1')");
    $db->exec("INSERT INTO calendars (id, components) VALUES (1, 'VEVENT')");
    $db->exec("INSERT INTO calendarinstances (id, calendarid, principaluri, uri) VALUES (1, 1, 'principals/user1', 'holidays')");

    $http = self::newExchange();
    $http->getRequest()->setMethod('POST');
    $http->getRequest()->setUrl('/calendar/tasks/holidays');
    $http->getRequest()->setHeader('Authorization', "Basic {$authz}");
    $http->getRequest()->setBody('{"year": 2018, "state": "BY"}');

    // act
    Server::run('/calendar', 'temp.sqlite', $http);

    // assert
    $this->assertEquals(200, $http->getResponse()->getStatus());
    $this->assertEquals(15, $db->querySingle("SELECT count(*) FROM calendarobjects"));
    $this->assertEquals(15, $db->querySingle("SELECT count(*) FROM calendarchanges"));
  }

  public function testShouldCreateNewSharedTodoList() {
    // arrange
    $authz = base64_encode('user1:dummypwd');
    $password = password_hash('dummypwd', PASSWORD_BCRYPT);

    $db = new \SQLite3('temp.sqlite');
    $db->exec("INSERT INTO users (id, username, password) VALUES (1, 'user1', '{$password}'), (2, 'user2', '{$password}')");
    $db->exec("INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1'), (2, 'principals/user2', 'user2')");

    $http = self::newExchange();
    $http->getRequest()->setMethod('POST');
    $http->getRequest()->setUrl('/calendar/tasks/todos');
    $http->getRequest()->setHeader('Authorization', "Basic {$authz}");
    $http->getRequest()->setBody('{"principals": ["principals/user1", "principals/user2"], "title": "Yet another TODO list", "uri": "yatodol"}');

    // act
    Server::run('/calendar', 'temp.sqlite', $http);

    // assert
    $this->assertEquals(200, $http->getResponse()->getStatus());
    $this->assertEquals(1, $db->querySingle("SELECT count(*) FROM calendars"));
    $this->assertEquals(2, $db->querySingle("SELECT count(*) FROM calendarinstances WHERE access=3 AND displayname='Yet another TODO list'"));
  }

  public function testShouldUnregisterUserAndCleanupCalendars() {
    // arrange
    $authz = base64_encode('user1:dummypwd');
    $password = password_hash('dummypwd', PASSWORD_BCRYPT);

    $db = new \SQLite3('temp.sqlite');
    $db->exec("INSERT INTO users (id, username, password) VALUES (1, 'user1', '{$password}'), (2, 'user2', '{$password}')");
    $db->exec("INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1'), (2, 'principals/user2', 'user2')");
    $db->exec("INSERT INTO calendars (id, components) VALUES (1, 'VEVENT'), (2, 'VEVENT'), (3, 'VTODO')");
    $db->exec("INSERT INTO calendarinstances (id, calendarid, access, principaluri, uri) VALUES (1, 1, 1, 'principals/user1', 'personal')");
    $db->exec("INSERT INTO calendarinstances (id, calendarid, access, principaluri, uri) VALUES (2, 2, 2, 'principals/user1', 'holidays')");
    $db->exec("INSERT INTO calendarinstances (id, calendarid, access, principaluri, uri) VALUES (3, 3, 3, 'principals/user1', 'todos')");
    $db->exec("INSERT INTO calendarinstances (id, calendarid, access, principaluri, uri) VALUES (4, 3, 3, 'principals/user2', 'todos')");

    $http = self::newExchange();
    $http->getRequest()->setMethod('POST');
    $http->getRequest()->setUrl('/calendar/tasks/unregister');
    $http->getRequest()->setHeader('Authorization', "Basic {$authz}");
    $http->getRequest()->setBody('{"principals": ["principals/user1"]}');

    // act
    Server::run('/calendar', 'temp.sqlite', $http);

    // assert
    $this->assertEquals(200, $http->getResponse()->getStatus());
    $this->assertEquals([2], $this->fetchAll($db, "SELECT id FROM principals"));
    $this->assertEquals([3], $this->fetchAll($db, "SELECT id FROM calendars"));
    $this->assertEquals([4], $this->fetchAll($db, "SELECT id FROM calendarinstances"));
  }

  public function testShouldCreateNewCalendarEvent() {
    // arrange
    $authz = base64_encode('user1:dummypwd');
    $password = password_hash('dummypwd', PASSWORD_BCRYPT);

    $db = new \SQLite3('temp.sqlite');
    $db->exec("INSERT INTO users (id, username, password) VALUES (1, 'user1', '{$password}')");
    $db->exec("INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1')");
    $db->exec("INSERT INTO calendars (id, components) VALUES (1, 'VEVENT,VTODO')");
    $db->exec("INSERT INTO calendarinstances (id, calendarid, principaluri, uri) VALUES (1, 1, 'principals/user1', 'default')");

    $http = self::newExchange();
    $http->getRequest()->setMethod('PUT');
    $http->getRequest()->setUrl('/calendar/calendars/user1/default/event.ics');
    $http->getRequest()->setHeader('Authorization', "Basic {$authz}");
    $http->getRequest()->setHeader('Content-Type', 'text/calendar');
    $http->getRequest()->setBody(''
      . "BEGIN:VCALENDAR\n"
      . "VERSION:2.0\n"
      . "PRODID:ical4j\n"
      . "BEGIN:VEVENT\n"
      . "DTSTAMP:20220111T091738Z\n"
      . "UID:0efb20b9-2cd0-4a83-baf9-cbbf072c3c9f\n"
      . "SEQUENCE:1\n"
      . "SUMMARY:Sample Event\n"
      . "DTSTART;TZID=Europe/Berlin:20211204T153000\n"
      . "DTEND;TZID=Europe/Berlin:20211204T163000\n"
      . "END:VEVENT\n"
      . "END:VCALENDAR");

    // act
    Server::run('/calendar', 'temp.sqlite', $http);

    // assert
    $this->assertEquals(201, $http->getResponse()->getStatus());
  }

  private static function fetchAll($db, $stmt) {
    $result = $db->query($stmt);
    if (!$result) return false;

    $values = array();
    while($value = $result->fetchArray(\SQLITE3_NUM)) {
        $values[] = $value[0];
    }
    return $values;
  }

  private static function newExchange() {
    return new class extends HTTP\Sapi {
      private static $request, $response;

      public static function getRequest(): HTTP\Request {
        return self::$request = self::$request ?? new HTTP\Request('GET', '/');
      }

      public static function getResponse(): HTTP\Response {
        return self::$response;
      }

      public static function sendResponse(HTTP\ResponseInterface $response) {
        self::$response = $response;
      }
    };
  }

}

?>