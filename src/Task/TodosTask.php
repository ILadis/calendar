<?php

namespace CalDAV\Task;

use CalDAV\Task\Backend;

class TodosTask extends Task {

  private $backend = null;

  public function __construct(Backend\BackendInterface $backend) {
    $this->backend = $backend;
  }

  public function run(string $principal, array $params): bool {
    if (parent::param($params, 'principals', '/principals\/[a-z0-9]+/', $principals) <= 0 ||
        parent::param($params, 'title', '/.+/',      $title) != 1 ||
        parent::param($params, 'uri',   '/[a-z-]+/', $uri)   != 1) {
      return false;
    }

    return $this->backend->newSharedTodoList($principals, current($title), current($uri));
  }
}

?>
