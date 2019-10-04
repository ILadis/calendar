<?php

namespace CalDAV\Test;

use Sabre\DAV\Collection;
use Sabre\DAV\Node;

class TestColl extends Collection {

  public function getName() {
    return "test coll !!";
  }

  public function getChildren() {
    return [new TestNode(), new TestNode(), new TestNode()];
  }
}

class TestNode extends Node {

  public function getName() {
    return "test node !!";
  }

}

?>
