<?php

namespace CalDAV\Setup\Backend;

class PDO implements BackendInterface {

  private $pdo = null;

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function hasSchema() {
    $query = 'SELECT count(*) AS count FROM sqlite_master WHERE type = ?';
    $stmt = $this->pdo->prepare($query);
    $stmt->execute(['table']);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    $count = $result['count'];

    return $count > 0;
  }

  public function createSchema() {
    $query = file_get_contents(__DIR__ . '/Schema.sql');
    $this->pdo->exec($query);
  }
}

?>
