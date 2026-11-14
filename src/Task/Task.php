<?php

namespace CalDAV\Task;

abstract class Task {
  public abstract function run(string $principal, array $params): bool;

  protected static function param(array &$params, string $key, string $pattern, &$values): int {
    if (!array_key_exists($key, $params)) {
      return 0;
    }

    $values = $params[$key];
    if (!is_array($values)) {
      $values = array($values);
    }

    foreach ($values as $value) {
      if (!preg_match_all($pattern, strval($value))) {
        return 0;
      }
    }

    return count($values);
  }
}

?>
