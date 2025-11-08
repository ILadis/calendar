#!/usr/bin/php
<?php

function main($args) {
  $action = $args[1] ?? '';
  $clean = in_array('--clean', $args);

  match ($action) {
    'build' => build($clean),
    'test' => test(),
  };
}

function build($clean = false) {
  Phar::canWrite() or die('Cannot build Phar when readonly flag is set!');

  $root = realpath(__DIR__);
  $target = "{$root}/calendar.phar";

  $vendor = dir_iterator($root, '/vendor');
  $source = dir_iterator($root, '/src');

  if ($clean) {
    remove($target);

    foreach ($vendor as $file) {
      remove($file);
    }
  }

  $composer = get_composer() or die('Could not get composer instance!');
  $composer('update --no-ansi') or die('Failed to update dependencies with composer!');

  $phar = new Phar($target);
  $phar->setDefaultStub('index.php');
  $phar->buildFromIterator($vendor, $root);
  $phar->buildFromIterator($source, $root);
  $phar->addFromString('index.php', ''
    . "<?php\n"
    . "function exception_error_handler(\$errno, \$errstr, \$errfile, \$errline) {\n"
    . "  throw new ErrorException(\$errstr, 0, \$errno, \$errfile, \$errline);\n"
    . "}\n"
    . "set_error_handler('exception_error_handler');\n"
    . "require_once __DIR__ . '/vendor/autoload.php';\n"
    . "CalDAV\Server::run();\n"
    . "?>");
}

function test() {
  $config = [
    '--display-all-issues',
    '--do-not-cache-result',
    '--bootstrap', 'vendor/autoload.php',
    '--testdox', 'test/'
  ];

  $phpunit = get_phpunit() or die('Could not get phpunit instance!');
  $phpunit($config) or die('There were phpunit test failures!');
}

function get_composer($version = '2.8.12', $hash = '048314f5f45feb38804d84ce4405f7ae3d736d58') {
  $target = 'composer.phar';
  $phar = false;

  if (!file_exists($target)) {
    $phar = file_get_contents("https://getcomposer.org/download/{$version}/composer.phar");
    file_put_contents($target, $phar);
  }

  $phar = file_get_contents($target);
  $sha = sha1($phar);

  if ($sha !== $hash) {
    return false;
  }

  require "phar://{$target}/src/bootstrap.php";

  $composer = new Composer\Console\Application();
  $composer->setAutoExit(false);

  return function($input) use ($composer) {
    $in = new Symfony\Component\Console\Input\StringInput($input);
    $code = $composer->run($in);
    return $code === 0;
  };
}

function get_phpunit($version = '12.4.2', $hash = 'db21f7072c5ce448279f83cf9696748e576186c8') {
  $target = 'phpunit.phar';
  $phar = false;

  if (!file_exists($target)) {
    $phar = file_get_contents("https://phar.phpunit.de/phpunit-{$version}.phar");
    file_put_contents($target, $phar);
  }

  $phar = file_get_contents($target);
  $sha = sha1($phar);

  if ($sha !== $hash) {
    return false;
  }

  require "phar://{$target}";

  $phpunit = new PHPUnit\TextUI\Application();

  return function($input) use ($phpunit) {
    $code = $phpunit->run($input);
    return $code === 0;
  };
}

function dir_iterator($root, $path) {
  $skipDots = RecursiveDirectoryIterator::SKIP_DOTS;
  $childFirst = RecursiveIteratorIterator::CHILD_FIRST;
  $iterator = new RecursiveDirectoryIterator($root . $path, $skipDots);
  return new RecursiveIteratorIterator($iterator, $childFirst);
}

function remove($file) {
  return is_dir($file) ? @rmdir($file) : @unlink($file);
}

if (PHP_SAPI == 'cli') {
  main($argv);
}

?>