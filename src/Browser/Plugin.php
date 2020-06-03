<?php

namespace CalDAV\Browser;

use CalDAV\ConsoleLogger;

use Sabre\DAV\Browser;
use Sabre\DAV\Exception;

class Plugin extends Browser\Plugin {

  private $logger = null;

  public function __construct($enablePost = true) {
    parent::__construct($enablePost);
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  protected function getLocalAssetPath($assetName) {
    $rootDir = \Phar::running();
    $assetDir = "{$rootDir}/vendor/sabre/dav/lib/DAV/Browser/assets/";

    $path = $assetDir.$assetName;
    $path = str_replace('\\', '/', $path);

    if (strpos($path, '..') !== false) {
      throw new Exception\NotFound('Path does not exist, or escaping from the base path was detected');
    }

    if (file_exists($path) !== true) {
      throw new Exception\NotFound('Path does not exist, or escaping from the base path was detected');
    }

    $this->logger->debug("Serving asset: {$assetName}");
    return $path;
  }
}

?>
