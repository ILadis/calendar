<?php

namespace CalDAV\Task;

use PHPUnit\Framework\TestCase;
use Sabre\HTTP;

class HolidaysTaskTest extends TestCase {

  private $sut;
  private $client;

  protected function setUp(): void {
    $this->client = $this->createStub(HTTP\Client::class);
    $this->sut = new HolidaysTask($this->client);
  }

  public function testShouldReturnNullIfParamsAreInvalid(): void {
    // arrange
    $params = array([
      'year' => 'Ox19F',
      'state' => 'XY'
    ], [
      'year' => 47,
      'state' => 11
    ]);

    foreach ($params as $param) {
      // act
      $events = $this->sut->run($param);

      // assert
      $this->assertNull($events);
    }
  }

  public function testShouldReturnNullIfResponseCodeIsNot200OK() {
    // arrange
    $response = new HTTP\Response();

    $codes = array(201, 300, 404, 500, 101);
    foreach ($codes as $code) {
      $response->setStatus($code);

      $this->client->method('send')
        ->willReturn($response);

      // act
      $events = $this->sut->run(['year' => '2016', 'state' => 'BY']);

      // assert
      $this->assertNull($events);
    }
  }

  public function testShouldCreateVCalendarsFromValidJsonResponse() {
    // arrange
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

    $this->client->method('send')
      ->willReturn($response);

    // act
    $events = $this->sut->run(['year' => '2016', 'state' => 'BY']);

    // assert
    $this->assertCount(3, $events);
    $this->assertEquals('Neujahrstag', $events[0]->VEVENT->SUMMARY);
    $this->assertEquals('Frohes Neues!', $events[0]->VEVENT->DESCRIPTION);
    $this->assertEquals('20160101', $events[0]->VEVENT->DTSTART);
    $this->assertEquals('Tag der Arbeit', $events[1]->VEVENT->SUMMARY);
    $this->assertEquals('Tag der Deutschen Einheit', $events[2]->VEVENT->SUMMARY);
  }

  public function testShouldNotCreateVCalendarsFromInvalidJsonResponse() {
    // arrange
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

      $this->client->method('send')
        ->willReturn($response);

      // act
      $events = $this->sut->run(['year' => '2016', 'state' => 'BY']);

      // assert
      $this->assertCount(0, $events);
    }
  }
}

?>
