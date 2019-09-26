<?php

namespace CalDAV\Setup\Backend;

class PDO implements BackendInterface {

  private $pdo = null;
  private $tableName = 'sqlite_master';

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function hasSchema(): bool {
    $query = 'SELECT count(*) AS count FROM '.$this->tableName.' WHERE type = ?';
    $stmt = $this->pdo->prepare($query);
    $stmt->execute(['table']);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    $count = intval($result['count']);

    return $count > 0;
  }

  public function createSchema(): bool {
    $query = file_get_contents(__DIR__ . '/Schema.sql');
    $result = $this->pdo->exec($query);

    return $result !== false;
  }
}

?>
