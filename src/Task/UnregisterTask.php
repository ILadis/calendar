<?php

namespace CalDAV\Task;

use CalDAV\Task\Backend;

class UnregisterTask extends Task {

  private $backend = null;

  public function __construct(Backend\BackendInterface $backend) {
    $this->backend = $backend;
  }

  public function run(string $principal, array $params): bool {
    if (Task::param($params, 'user', '/[a-zA-Z0-9]+/', $user) != 1) {
      return false;
    }

    return $this->backend->unregisterUser(current($user));
  }
}

?>
