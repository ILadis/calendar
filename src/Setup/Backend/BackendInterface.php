<?php

namespace CalDAV\Setup\Backend;

interface BackendInterface {
  public function hasSchema();
  public function createSchema();
}

?>
