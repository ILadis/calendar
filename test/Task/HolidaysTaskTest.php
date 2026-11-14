<?php

namespace CalDAV\Task;

use PHPUnit\Framework\TestCase;
use CalDAV\Calendar;
use Sabre\CalDAV;
use Sabre\HTTP;

class HolidaysTaskTest extends TestCase {

  private $backend;
  private $client;
  private $sut;
  
  protected function setUp(): void {
    $this->backend = $this->createStub(Calendar\Backend\BackendInterface::class);
    $this->client = $this->createStub(HTTP\Client::class);
    $this->sut = new HolidaysTask($this->backend, $this->client);
  }

  public function testShouldReturnFalseIfParamsAreInvalid() {
    // arrange
    $principal = 'user1';
    $params = array([
      'year' => 'Ox19F',
      'state' => 'XY'
    ], [
      'year' => 47,
      'state' => 11
    ]);

    foreach ($params as $param) {
      // act
      $result = $this->sut->run($principal, $param);

      // assert
      $this->assertFalse($result);
    }
  }

  public function testShouldReturnFalseIfNoHolidaysCalendarForPrincipalExists() {
    // arrange
    $principal = 'user1';
    $params = ['year' => '2016', 'state' => 'BY'];

    $this->backend->method('findCalendarId')->with($principal, 'holidays')->willReturn(null);

    // act
    $result = $this->sut->run($principal, $params);

      // assert
    $this->assertFalse($result);
  }

  public function testShouldReturnFalseIfResponseCodeIsNot200Ok() {
    // arrange
    $principal = 'user1';
    $params = ['year' => '2016', 'state' => 'BY'];

    $response = new HTTP\Response();
    $codes = array(201, 300, 404, 500, 101);
    foreach ($codes as $code) {
      $response->setStatus($code);

      $this->backend->method('findCalendarId')->with($principal, 'holidays')->willReturn(['2']);
      $this->client->method('send')->willReturn($response);

      // act
      $result = $this->sut->run($principal, $params);

      // assert
      $this->assertFalse($result);
    }
  }

  public function testShouldNotCreateVCalendarsFromInvalidJsonResponse() {
    // arrange
    $events = array();
    $principal = 'user1';
    $params = ['year' => '2016', 'state' => 'BY'];

    $response = new HTTP\Response();
    $response->setStatus(200);

    $bodies = array(
      '[20160101, 20170101]',
      '[{"title": "Neujahrstag"}]',
      '{"title": "Neujahrstag"}',
      '{"Neujahrstag": { "heinweis": "" }}',
      '{"Neujahrstag": { "datum": "1. Januar 2016" }}'
    );
    foreach ($bodies as $body) {
      $response->setBody($body);

      $this->client->method('send')->willReturn($response);
      $this->backend->method('findCalendarId')->with($principal, 'holidays')->willReturn(['2']);
      $this->backend->method('saveEvent')->willReturnCallback(function($calendarId, $event) use (&$events) {
        $events[] = $event;
      });

      // act
      $result = $this->sut->run($principal, $params);

      // assert
      $this->assertTrue($result);
      $this->assertCount(0, $events);
    }
  }

  public function testShouldCreateVCalendarsFromValidJsonResponse() {
    // arrange
    $events = array();
    $principal = 'user1';
    $calendar = ['id' => ['2'], 'uri' => 'holidays'];
    $params = ['year' => '2016', 'state' => 'BY'];

    $response = new HTTP\Response();
    $response->setStatus(200);
    $response->setBody(''
      . '{'
      . '  "Neujahrstag": {'
      . '    "datum": "2016-01-01",'
      . '    "hinweis": "Frohes Neues!" },'
      . '  "Tag der Arbeit": {'
      . '    "datum": "2016-05-01" },'
      . '  "Tag der Deutschen Einheit": {'
      . '    "datum":"2016-10-03" }'
      . '}');

    $this->client->method('send')->willReturn($response);
    $this->backend->method('findCalendarId')->with($principal, 'holidays')->willReturn(['2']);
    $this->backend->method('saveEvent')->willReturnCallback(function($calendarId, $event) use (&$events) {
      $events[] = $event;
    });

    // act
    $result = $this->sut->run($principal, $params);

    // assert
    $this->assertTrue($result);
    $this->assertCount(3, $events);
    $this->assertEquals('Neujahrstag', $events[0]->VEVENT->SUMMARY);
    $this->assertEquals('Frohes Neues!', $events[0]->VEVENT->DESCRIPTION);
    $this->assertEquals('20160101', $events[0]->VEVENT->DTSTART);
    $this->assertEquals('Tag der Arbeit', $events[1]->VEVENT->SUMMARY);
    $this->assertEquals('Tag der Deutschen Einheit', $events[2]->VEVENT->SUMMARY);
  }
}

?>
