<?php

namespace CalDAV\Task;

interface Task {
  public function run(array $params): ?array;
}

?>
