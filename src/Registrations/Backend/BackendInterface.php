<?php

namespace CalDAV\Registrations\Backend;

interface BackendInterface {
  public function isPending(string $username): bool;
  public function markCompleted(string $username): void;
}

?>
