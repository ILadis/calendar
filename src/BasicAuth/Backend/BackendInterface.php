<?php

namespace CalDAV\BasicAuth\Backend;

interface BackendInterface {
  public function createNewUser(string $username, string $password): bool;
}

?>
