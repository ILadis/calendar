<?php

namespace CalDAV\User\Backend;

use Sabre\DAV\Auth\Backend\AbstractBasic;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class PDO extends AbstractBasic implements BackendInterface {

  private $pdo = null;

  public function __construct(\PDO $pdo) {
    $this->pdo = $pdo;
    $this->realm = 'CalDAV';
  }

  public function check(RequestInterface $request, ResponseInterface $response) {
    $result = parent::check($request, $response);

    if ($result[0]) {
      // on successful authentication change to lower case principal name
      $result[1] = strtolower($result[1]);
    }

    return $result;
  }

  public function createNewUser(string $username, string $password): bool {
    // TODO validate username (should only contain [a-zA-Z0-9])

    $uri = 'principals/'. strtolower($username);
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $this->pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $result = $stmt->execute([$username, $hash]);

    if ($result) {
      $stmt = $this->pdo->prepare('INSERT INTO principals (uri, displayname) VALUES (?, ?)');
      $result = $stmt->execute([$uri, $username]);
    }

    return $result;
  }

  protected function validateUserPass($username, $password) {
    $stmt = $this->pdo->prepare('SELECT password FROM users WHERE username = ?');
    $stmt->execute([$username]);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$result) {
      return false;
    }

    $hash = strval($result['password']);
    $valid = password_verify($password, $hash);

    return $valid;
  }
}

?>
