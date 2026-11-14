<?php

namespace CalDAV\Task;

use CalDAV\Calendar;

class TodosTask implements Task {

  private $backend = null;

  public function __construct(Calendar\Backend\BackendInterface $backend) {
    $this->backend = $backend;
  }

  public function run(string $principal, array $params): bool {
    $valid = $this->verifyParams($params, $principals, $title, $uri);
    if (!$valid) {
      return false;
    }

    return $this->backend->newSharedTodoList($principals, $title, $uri);
  }

  private function verifyParams(array $params, &$principals, &$title, &$uri): bool {
    $principals = $params['principals'];
    if (!is_array($principals) || empty($principals)) {
      return false;
    }

    $title = $params['title'];
    if (!is_string($title) || empty($title)) {
      return false;
    }

    $uri = $params['uri'];
    if (!is_string($title) || empty($title) || !preg_match('|^[a-z-]+$|', $uri)) {
      return false;
    }

    return true;
  }
}

?>
