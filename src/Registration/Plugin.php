<?php

namespace CalDAV\Registration;

use CalDAV\ConsoleLogger;
use CalDAV\Registration;
use CalDAV\User;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\Auth\Basic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

  private $registrations = null;
  private $users = null;
  private $logger = null;

  public function __construct(Registration\Backend\BackendInterface $registrations, User\Backend\BackendInterface $users) {
    $this->registrations = $registrations;
    $this->users = $users;
    $this->logger = ConsoleLogger::for(Plugin::class);
  }

  public function initialize(Server $server) {
    $server->on('beforeMethod:*', [$this, 'registerUser'], 5);
  }

  public function registerUser(RequestInterface $request, ResponseInterface $response): void {
    $digest = new Basic('CalDAV', $request, $response);

    $credentials = $digest->getCredentials();
    if ($credentials) {
      $username = $credentials[0];
      $password = $credentials[1];

      $pending = $this->registrations->isPending($username);
      if ($pending) {
        $this->logger->info("There is a pending registration for '{$username}', creating user now");
        $this->users->createNewUser($username, $password);
        $this->registrations->markCompleted($username);
        $this->logger->info("Successfully created user '{$username}'");
      }
    }
  }
}

?>
