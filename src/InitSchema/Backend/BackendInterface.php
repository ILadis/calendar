<?php

namespace CalDAV\InitSchema\Backend;

interface BackendInterface {
  public function hasSchema();
  public function createSchema();
}

?>
