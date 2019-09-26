<?php

namespace CalDAV\Users\Backend;

interface BackendInterface {
  public function createNewUser(string $username, string $password): bool;
}

?>
