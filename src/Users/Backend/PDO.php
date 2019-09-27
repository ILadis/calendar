<?php

namespace CalDAV\Users\Backend;

use Sabre\DAV\Auth\Backend\AbstractBasic;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class PDO extends AbstractBasic implements BackendInterface {

  private $pdo = null;
  private $tableName = 'users';

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
    $this->realm = 'CalDAV';
  }

  public function check(RequestInterface $request, ResponseInterface $response) {
    $result = parent::check($request, $response);

    if ($result[0]) {
      $result[1] = strtolower($result[1]);
    }

    return $result;
  }

  public function createNewUser(string $username, string $password): bool {
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $this->pdo->prepare('INSERT INTO '.$this->tableName .' (username, password) VALUES (?, ?)');
    $result = $stmt->execute([$username, $hash]);

    return $result;
  }

  protected function validateUserPass($username, $password) {
    $stmt = $this->pdo->prepare('SELECT password FROM '.$this->tableName.' WHERE username = ?');
    $stmt->execute([$username]);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    $hash = strval($result['password']);

    $valid = password_verify($password, $hash);

    return $valid;
  }
}

?>
