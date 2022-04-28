<?php

namespace CalDAV\Admin\Backend;

class PDO implements BackendInterface {

  private $pdo = null;

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function newSharedTodoList(array $principals, string $title, string $uri): bool {
    if (!$this->validateParameters($principals, $title, $uri) || !$this->validatePrincipals($principals)) {
      return false;
    }

    $stmt = $this->pdo->prepare('INSERT INTO calendars (components) VALUES ("VTODO")');
    $result = $stmt->execute();

    $id = intval($this->pdo->lastInsertId());
    $access = 3; // shared

    if (!$result || $id <= 0) {
      return false;
    }

    foreach ($principals as $principal) {
      $stmt = $this->pdo->prepare('INSERT INTO calendarinstances (calendarid, principaluri, access, displayname, uri) VALUES (?, ?, ?, ?, ?)');
      $result = $stmt->execute([$id, $principal, $access, $title, $uri]);

      if (!$result) {
        return false;
      }
    }

    return true;
  }

  private function validateParameters(array $principals, string $title, string $uri): bool {
    if (!is_array($principals) || !is_string($title) || !is_string($uri)) {
      return false;
    }

    if (empty($principals) || empty($title) || empty($uri)) {
      return false;
    }

    if (!preg_match('|^[a-z-]+$|', $uri)) {
      return false;
    }

    return true;
  }

  private function validatePrincipals(array $principals): bool {
    foreach ($principals as $principal) {
      $id = $this->fetchPrincipalId($principal);
      if ($id == -1) {
        return false;
      }
    }

    return true;
  }

  private function fetchPrincipalId(string $principal): int {
    $stmt = $this->pdo->prepare('SELECT id FROM principals WHERE uri = ?');
    $stmt->execute([$principal]);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return intval($result['id'] ?? -1);
  }
}

?>
