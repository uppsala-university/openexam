<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/exam/register.php
// Author: Anders Lövgren
// Date:   2010-09-08
//
// This script provides the registry service for fwexamd services. The fwexamd
// application is a service runned on remote (examination client) computers and
// provides the lockdown service for an examination.
//
// This script is responsible for storing information about the peer (i.e. the
// dynamic password) that can later be used when talking to the fwexamd service.
//
// By nature it's the same computers that should have access to examinations
// that needs to access this script. No further access control needs to be done.
//
// Please bare in mind that these classes is *not* communicating with an web
// browser. When running under a web server its assumed that the peer is an
// agent understanding basic HTTP headers. In service mode, we assume that the
// peer is understanding the telnet protocol (text based messages).
//
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

if (!defined('SERVICE_BIND_ADDR')) {
        define('SERVICE_BIND_ADDR', '0.0.0.0');
}
if (!defined('SERVICE_BIND_PORT')) {
        define('SERVICE_BIND_PORT', 3751);
}
if (!defined('SERVICE_SOCKET_TYPE')) {
        define('SERVICE_SOCKET_TYPE', 'tcp');
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
        const CREATED = 1;
        const UPDATED = 2;

        //
        // Store the password for remote addr service.
        //
        protected function store($pass, $addr, $port = 0)
        {
                $db = Database::getConnection();

                //
                // Find out whether to create or update:
                //
                $sql = sprintf("SELECT COUNT(*) FROM computers
                                WHERE ipaddr = '%s' AND port = %d", $addr, $port);
                $db->setFetchMode(MDB2_FETCHMODE_ORDERED);

                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();
                $ret = $row[0] == 0 ? self::CREATED : self::UPDATED;
                $db->setFetchMode(MDB2_FETCHMODE_ASSOC);

                //
                // Insert new computer or updated existing record:
                //
                if ($ret == self::CREATED) {
                        $sql = sprintf("INSERT INTO computers(ipaddr, port, password, created)
                                        VALUES('%s',%d,'%s','%s')",
                                        $addr, $port, $pass, strftime(DATETIME_FORMAT));
                } else {
                        $sql = sprintf("UPDATE computers SET password = '%s'
                                        WHERE ipaddr = '%s' AND port = %d",
                                        $pass, $addr, $port);
                }
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }

                return $ret;
        }

}

// 
// This class implements an handler that should be runned under a web server.
//
class RegisterHandler extends Register
{

        private $addr;  // IPv4 or IPv6 (possibly tunneled)
        private $port;  // facilate hosts behind NAT
        private $pass;  // The remote password

        public function __construct()
        {
                $this->addr = $_SERVER['REMOTE_ADDR'];

                if (isset($_POST['pass'])) {
                        $this->pass = $_POST['pass'];
                }
                if (isset($_POST['port'])) {
                        $this->port = $_POST['port'];
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
                $result = $this->store($_SERVER['REMOTE_ADDRESS'], $pass);
                if ($result == Register::CREATED) {
                        echo "OK: created\r\n";
                } elseif ($result == Register::UPDATED) {
                        echo "OK: updated\r\n";
                }
        }

}

class RegisterClient extends Register
{

        private $socket;
        private $addr;
        private $port;
        private $pass;

        public function __construct($socket)
        {
                $this->socket = $socket;
        }

        public function handle()
        {
                if (!socket_getpeername($this->socket, $this->addr)) {
                        $mess = "Get peer address failed (getpeername)";
                        $code = socket_last_error($this->socket);
                        throw new RegisterException($mess, $code);
                }
                while ($str = fgets($this->socket)) {
                        list($key, $val) = explode("=", $str);
                        switch (trim($key)) {
                                case "password":
                                case "pass":
                                        $this->pass = trim($val);
                                        break;
                                case "port":
                                        $this->port = trim($val);
                                        break;
                                default:
                                        throw new RegisterException(sprintf("Invalid command %s", $key));
                        }
                }
                if (!isset($pass)) {
                        throw new RegisterException("Missing required command 'password'");
                }

                $result = $this->store($pass, $addr, $port);
                if ($result == Register::CREATED) {
                        fwrite($client, "OK: created\r\n");
                } elseif ($result == Register::UPDATED) {
                        fwrite($client, "OK: updated\r\n");
                }
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
                        throw new RegisterException($errstr, $errno);
                }
        }

        //
        // Begin handling client requests.
        //
        public function handle()
        {
                while ($client = stream_socket_accept($this->socket)) {
                        try {
                                $peer = new RegisterClient($client);
                                $peer->handle();
                        } catch (DatabaseException $exception) {
                                error_log($exception);
                                fwrite($client, "ERROR: internal server error\r\n");
                        } catch (RegisterException $exception) {
                                if ($exception->getCode() != 0) {
                                        error_log(sprintf("%s: %s (%d)",
                                                        $exception->getMessage(),
                                                        socket_strerror($exception->getCode()),
                                                        $exception->getCode()));
                                } else {
                                        error_log($exception);
                                }
                                fwrite($client, "ERROR: %s\r\n", $exception);
                        }
                }
                fclose($client);
        }

}

if (isset($_SERVER['SERVER_ADDR'])) {
        try {
                $register = new RegisterHandler();
                $register->handle();
        } catch (RegisterException $exception) {
                header(sprintf("HTTP/1.0 %d %s", $exception->getCode(),
                                $exception->getMessage()));
                error_log($exception);
                exit(1);
        } catch (DatabaseException $exception) {
                header("HTTP/1.0 500 Internal Server Error");
                error_log($exception);
                exit(1);
        }
} else {
        $register = new RegisterService();
        $register->setup();
        $register->handle();
}
?>
