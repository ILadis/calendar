<?php

namespace CalDAV;

use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger {

  public static function for(string $name) {
    return new ConsoleLogger($name);
  }

  private $name = null;

  private function __construct(string $name) {
    $this->name = $name;
  }

  public function log($level, $message, array $context = array()) {
    $date = $this->currentDateTime();
    $name = $this->name;

    $log = sprintf("%s [%9s] --- %s: %s\n", $date, $level, $name, $message);
    file_put_contents('php://stdout', $log);

    if (array_key_exists('exception', $context)) {
      $exception = strval($context['exception']);
      file_put_contents('php://stdout', $exception);
    }
  }

  private function currentDateTime(): string {
    $now = new \DateTime();
    $date = $now->format('Y-m-d H:i:s:v');
    return $date;
  }
}

?>
