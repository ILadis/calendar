<?php

namespace CalDAV\Admin\Backend;

interface BackendInterface {
  public function newSharedTodoList(array $principals, string $displayName, string $uri): bool;
}

?>
