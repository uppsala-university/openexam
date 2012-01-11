<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/exam/register.php
// Author: Anders Lövgren
// Date:   2010-09-08
//
// ** Purpose:
//    --------------------
//    This script provides a registry service for fwexamd services. The fwexamd
//    application is a service runned on remote (examination client) computers
//    and provides the lockdown service for an examination.
//
//    This script is responsible for storing information about the peer (i.e.
//    the dynamic password) that can later be used when talking to the fwexamd
//    service.
//
// ** Access control:
//    --------------------
//    By nature it's the same computers that should have access to examinations
//    that needs to access this script. No further access control needs to be
//    done.
//
// ** Notice:
//    --------------------
//    Please bare in mind that these classes is *not* communicating with an web
//    browser. When running under a web server its assumed that the peer is an
//    agent understanding basic HTTP headers. In service mode, we assume that
//    the peer is understanding the telnet protocol (text based messages).
//
// ** Commands:
//    --------------------
//    Both RegisterHandler (HTTP mode) and RegisterClient (standalone service)
//    supports the pass, addr and port commands. The addr and port commands is
//    useful when register a lock down service behind a firewall (in NAT mode).
//
// System check:
//
if (!file_exists("../../conf/database.conf")) {
        die("internal server error");
}
if (!file_exists("../../conf/config.inc")) {
        die("internal server error");
}

//
// Set custom include path:
//
if (!isset($_SERVER['SERVER_ADDR'])) {
        set_include_path(get_include_path() . PATH_SEPARATOR . "../..");
}

//
// Include external libraries:
//
include "MDB2.php";

//
// Include configuration:
//
include "conf/config.inc";
include "conf/database.conf";

//
// Include database support:
//
include "include/database.inc";

//
// Include businness logic:
//
include "include/locker.inc";

if (!defined('SERVICE_BIND_ADDR')) {
        define('SERVICE_BIND_ADDR', '0.0.0.0');
}
if (!defined('SERVICE_BIND_PORT')) {
        define('SERVICE_BIND_PORT', 3751);
}
if (!defined('SERVICE_SOCKET_TYPE')) {
        define('SERVICE_SOCKET_TYPE', 'tcp');
}
if (!defined('SERVICE_NAME')) {
        define('SERVICE_NAME', 'openexam-regsvc');
}
if (!defined('SERVICE_VERSION')) {
        define('SERVICE_VERSION', '1.0.0');
}

class RegisterException extends Exception
{

        public function __construct($message, $code = 0)
        {
                parent::__construct($message, $code);
        }

}

//
// The base class for register handlers.
//
class Register
{

        //
        // Store the password for remote addr service.
        //
        protected function store($pass, $addr, $port = 0)
        {
                $service = new LockerService($pass, $addr, $port);
                return $service->register();
        }

}

// 
// This class implements an handler that should be runned under a web server.
//
class RegisterHandler extends Register
{

        private $addr;  // IPv4 or IPv6 (possibly tunneled)
        private $port;  // Facilate hosts behind NAT
        private $pass;  // The remote password

        public function __construct()
        {
                $this->addr = $_SERVER['REMOTE_ADDR'];

                if (isset($_POST['pass'])) {
                        $this->pass = $_POST['pass'];
                }
                if (isset($_POST['addr'])) {
                        $this->addr = $_POST['addr'];
                }
                if (isset($_POST['port'])) {
                        $this->port = $_POST['port'];
                } else {
                        $this->port = SERVICE_BIND_PORT;
                }

                if (!isset($this->pass)) {
                        throw new RegisterException("Bad Request", 400);
                }
        }

        //
        // Handle client request passed thru the web server. The client is
        // assumend to understand basic HTTP headers and status codes.
        //
        public function handle()
        {
                $result = $this->store($this->pass, $this->addr, $this->port);
                if ($result == LockerService::CREATED) {
                        echo "OK: created\r\n";
                } elseif ($result == LockerService::UPDATED) {
                        echo "OK: updated\r\n";
                }
        }

}

//
// This class handles a single peer connection when running in standalone mode.
// 
class RegisterClient extends Register
{

        const PROTO_MAJOR = 0;
        const PROTO_MINOR = 8;

        private $socket;
        private $addr;
        private $port;
        private $pass;

        public function __construct($socket)
        {
                $this->socket = $socket;

                $this->addr = stream_socket_get_name($this->socket, true);
                $this->port = SERVICE_BIND_PORT;
                $this->pass = null;

                if (strchr($this->addr, ":")) {
                        $match = array();
                        if (preg_match("/^(.*):\d+/", $this->addr, $match)) {
                                $this->addr = $match[1];  // matched IPv4
                        } elseif (preg_match("/^(\[.*\]):\d+/", $this->addr, $match)) {
                                $this->addr = $match[1];  // matched IPv6
                        }
                }
        }

        //
        // Handles the client request.
        //
        public function handle()
        {
                $this->greet();

                while ($str = fgets($this->socket)) {
                        list($key, $val) = explode("=", trim($str));
                        if (strlen($key) == 0) {
                                break;
                        }
                        switch ($key) {
                                case "pass":
                                        $this->pass = $val;
                                        break;
                                case "addr":
                                        $this->addr = $val;
                                        break;
                                case "port":
                                        $this->port = $val;
                                        break;
                                case "help":
                                case "?":
                                case "/?":
                                        $this->usage();
                                        break;
                                default:
                                        throw new RegisterException(sprintf("Command '%s' is not supported", $key));
                        }
                }
                if (!isset($this->pass)) {
                        throw new RegisterException("Missing required command 'pass=str'");
                }

                $result = $this->store($this->pass, $this->addr, $this->port);
                if ($result == Register::CREATED) {
                        fwrite($this->socket, "OK: created\r\n");
                } elseif ($result == Register::UPDATED) {
                        fwrite($this->socket, "OK: updated\r\n");
                }
        }

        private function greet()
        {
                fprintf($this->socket, "%s %s [%d.%d] ready to serve (see help)\r\n", SERVICE_NAME, SERVICE_VERSION, self::PROTO_MAJOR, self::PROTO_MINOR);
        }

        private function usage()
        {
                fwrite($this->socket, "Supported commands:\r\n");
                fwrite($this->socket, "-------------------\r\n");
                fwrite($this->socket, "\r\n");
                fwrite($this->socket, "pass=str : Define the password for the lock down service (required)\r\n");
                fwrite($this->socket, "addr=str : The ipv4/ipv6 gateway address to connect thru (NAT mode)\r\n");
                fwrite($this->socket, "port=num : The gateway port that forwards connections (NAT mode)\r\n");
                fwrite($this->socket, "\r\n");
        }

}

//
// This class implements a standalone register service.
//
class RegisterService extends Register
{

        private $socket;
        private $addr;
        private $port;
        private $type;
        private $verbose = true;

        public function __construct($addr = SERVICE_BIND_ADDR, $port = SERVICE_BIND_PORT, $type = SERVICE_SOCKET_TYPE)
        {
                $this->addr = $addr;
                $this->port = $port;
                $this->type = $type;
        }

        public function __destruct()
        {
                fclose($this->socket);
        }

        //
        // Setup the service.
        //
        public function setup()
        {
                $bind = sprintf("%s://%s:%d", $this->type, $this->addr, $this->port);
                $this->socket = stream_socket_server($bind, $errno, $errstr);
                if (!$this->socket) {
                        throw new RegisterException(sprintf(
                                        "Failed create service: %s (%d)", $errstr, $errno)
                        );
                }
        }

        //
        // Begin handling client requests.
        //
        public function handle()
        {
                if ($this->verbose) {
                        printf("Service ready listening on %s:%d (%s)\n", $this->addr, $this->port, $this->type);
                }

                while ($client = stream_socket_accept($this->socket, -1, $this->peer)) {
                        if ($this->verbose) {
                                printf("Accepted client connection from %s\n", $this->peer);
                        }
                        try {
                                $remote = new RegisterClient($client);
                                $remote->handle();
                        } catch (DatabaseException $exception) {
                                error_log($exception);
                                fprintf($client, "ERROR: internal server error\r\n");
                        } catch (RegisterException $exception) {
                                error_log($exception);
                                fprintf($client, "ERROR: %s\r\n", $exception->getMessage());
                        }
                        fclose($client);
                }
        }

}

if (isset($_SERVER['SERVER_ADDR'])) {
        try {
                $register = new RegisterHandler();
                $register->handle();
        } catch (RegisterException $exception) {
                header(sprintf("HTTP/1.0 %d %s", $exception->getCode(), $exception->getMessage()));
                error_log($exception);
                exit(1);
        } catch (DatabaseException $exception) {
                header("HTTP/1.0 500 Internal Server Error");
                error_log($exception);
                exit(1);
        }
} else {
        try {
                $register = new RegisterService();
                $register->setup();
                $register->handle();
        } catch (RegisterException $exception) {
                error_log($exception);
        }
}
?>
