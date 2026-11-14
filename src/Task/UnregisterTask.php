<?php

namespace CalDAV\Task;

use CalDAV\Task\Backend;

class UnregisterTask implements Task {

  private $backend = null;

  public function __construct(Backend\BackendInterface $backend) {
    $this->backend = $backend;
  }

  public function run(string $principal, array $params): bool {
    $valid = $this->verifyParams($params, $principals);
    if (!$valid) {
      return false;
    }

    return $this->backend->deletePrincipal($principals[0]);
  }

  private function verifyParams(array $params, &$principals): bool {
    $principals = $params['principals'];
    if (!is_array($principals) || count($principals) != 1) {
      return false;
    }

    return true;
  }
}

?>
