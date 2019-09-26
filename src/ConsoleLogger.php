<?php

namespace CalDAV;

use DateTime;
use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger {

  public static function for($name) {
    return new ConsoleLogger($name);
  }

  private $name = null;

  private function __construct($name) {
    $this->name = $name;
  }

  public function log($level, $message, array $context = array()) {
    $date = $this->currentDateTime();
    $name = $this->name;

    $log = sprintf("%s [%9s] --- %s: %s\n", $date, $level, $name, $message);
    file_put_contents('php://stdout', $log);
  }

  private function currentDateTime() {
    $now = new DateTime();
    $date = $now->format('Y-m-d H:i:s:v');
    return $date;
  }
}

?>
