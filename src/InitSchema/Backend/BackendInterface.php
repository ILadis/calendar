<?php

namespace CalDAV\InitSchema\Backend;

interface BackendInterface {
  public function hasSchema(): bool;
  public function createSchema(): bool;
}

?>
