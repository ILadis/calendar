<?php

namespace CalDAV\Task;

use CalDAV\Task\Backend;

class UnregisterTask extends Task {

  private $backend = null;

  public function __construct(Backend\BackendInterface $backend) {
    $this->backend = $backend;
  }

  public function run(string $principal, array $params): bool {
    if (parent::param($params, 'principals', '/principals\/[a-z0-9]+/', $principals) != 1) {
      return false;
    }

    return $this->backend->deletePrincipal(current($principals));
  }
}

?>
