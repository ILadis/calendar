<?php

namespace CalDAV\Registrations\Backend;

class PDO implements BackendInterface {

  private $pdo = null;
  private $tableName = 'registrations';

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function isPending(string $username): bool {
    $stmt = $this->pdo->prepare('SELECT count(*) AS count FROM '.$this->tableName.' WHERE username = ? AND completed IS NULL');
    $stmt->execute([$username]);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    $count = intval($result['count']);

    return $count == 1;
  }

  public function markCompleted(string $username): void {
    $now = new \DateTime();
    $date = $now->format('Y-m-d H:i:s:v');

    $stmt = $this->pdo->prepare('UPDATE '.$this->tableName.' SET completed = ? WHERE username = ?');
    $stmt->execute([$date, $username]);
  }
}

?>
