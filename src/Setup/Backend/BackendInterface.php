<?php

namespace CalDAV\Setup\Backend;

interface BackendInterface {
  public function hasSchema(): bool;
  public function createSchema(): bool;
}

?>
