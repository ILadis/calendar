<?php

namespace CalDAV\User\Backend;

interface BackendInterface {
  public function createNewUser(string $username, string $password): bool;
  public function deleteUser(string $username): false|string;
}

?>
