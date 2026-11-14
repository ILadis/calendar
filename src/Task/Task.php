<?php

namespace CalDAV\Task;

interface Task {
  public function run(string $principal, array $params): bool;
}

?>
