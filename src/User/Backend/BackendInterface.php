<?php

namespace CalDAV\User\Backend;

interface BackendInterface {
  public function createNewUser(string $username, string $password): bool;
}

?>
