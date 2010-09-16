<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/locker.inc
// Author: Anders Lövgren
// Date:   2010-04-21
//
// This file provides classes for communication with the fwexamd lockdown
// daemon/service running on client computers perticipating in an online exam
// requiring lockdown. It's worth noticing that lockdown is optional.
// 
// The purpose of the locker daemon on the client computer is to configure
// the local firewall to only accept outgoing connections to those resources
// (actually URL:s to i.e. a video clip).
// 
// The locker service might be configured to register itself in the database
// at startup, but its also possible to have static registrations of lockdown
// services.
// 
// The resources (URL:s) are defined along with the questions in our database.
// The lockdown protocol is really simple, it consists of plain text messages
// like this:
// 
//   "initialize"      // Perform initial lockdown, open firewall to common resources.
//   "permit ipaddr"   // Permit access to this ip-address.
//   "restore"         // Restore the computer state to pre-initialize state.
//
// The protocol supports authentication and session mode (sending multiple
// commands over the same connection).
//
// All commands should be terminated by a newline. The client responds with
// an "OK" or "ERROR: message".
//
//
// The default port number of the fwexamd service:
//
if (!defined("FWEXAMD_PORT")) {
        define("FWEXAMD_PORT", 3751);
}

//
// The base class for the more specialized exceptions. This exception class
// should not be used directly.
// 
class LockerException extends Exception
{

        private $errmsg;  // Extended information, not for public.

        function __construct($pubmsg, $code = 0, $errmsg = null)
        {
                parent::__construct($pubmsg, $code);
                $this->errmsg = $errmsg;
        }

        public function getError()
        {
                return $this->errmsg;
        }

        function __toString()
        {
                return $this->message;
        }

}

//
// Exception for connect error.
//
class ConnectException extends LockerException
{

        private $port;
        private $addr;

        public function __construct($errmsg, $code, $addr, $port)
        {
                $this->port = $port;
                $this->addr = $addr;

                $errmsg = sprintf("Failed connect to %s:%d (%s)", $addr, $port, $errmsg);
                $pubmsg = _("A connection could not be established with the lockdown service for your computer. The fwexamd daemon/service is a required component that must to be accessable from this server to ensure an safe examination environment.");

                parent::__construct($pubmsg, $code, $errmsg);
        }

        public function getPort()
        {
                return $this->port;
        }

        public function getAddress()
        {
                return $this->addr;
        }

}

//
// Exception for unconnected socket exception (a kind of logic error).
// 
class InvalidStateException extends LockerException
{

        public function __construct()
        {
                $pubmsg = _("The communication with the lock down service (fwexamd) was lost. Maybe theres a network or system problem with your computer?");
                $errmsg = "The TCP socket is not connected to peer service.";
                parent::__construct($pubmsg, LockerProtocol::PROTO_STATE, $errmsg);
        }

}

//
// Protocol communication exception.
//
class ProtocolException extends LockerException
{

        public function __construct($errmsg, $code, $command)
        {
                $errmsg = sprintf("Protocol error: failed '%s' request (%s)", $command, $errmsg);
                $pubmsg = sprintf(_("Failed send the '%s' command to the lock down service running on your computer. Please report this to the system manager."), $command);
                parent::__construct($pubmsg, $code, $errmsg);
        }

}

//
// The interface for protocol callback.
//
interface ProtocolCallback
{

        //
        // Return the password for authenticating with the lock down service
        // running on host listening on port.
        // 
        public function password($ipaddr, $port);
}

// 
// The locker class implementing the lockdown protocol.
// 
class LockerProtocol
{
        const PROTO_STATE = 0; // Invalid state
        const PROTO_INITIALIZE = -1;
        const PROTO_PERMIT = -2;
        const PROTO_RESTORE = -3;
        const PROTO_BEGIN = -4;
        const PROTO_END = -5;
        const PROTO_AUTH = -6;
        const PROTO_GREETING = -7;

        private $ipaddr;           // The peer ip-address
        private $port;             // The destination port
        private $sock = null;      // The TCP socket
        //
        // These are initialized by the handshake procedure:
        //
        private $service = null;   // Service name
        private $version = null;   // Remote service version
        private $feature = "";     // Feature string (mode)
        private $proto = null;     // Protocol version
        private $master = false;   // Master mode enabled
        private $hash = null;      // Hashing algorithm
        private $auth = false;     // Authentication required
        //
        // Variables related to auth command:
        // 
        private $callback = null;  // Protocol callback object
        private $nonce = null;

        //
        // The connection is initial unconnected.
        //

        public function __construct($ipaddr, $port)
        {
                $this->ipaddr = $ipaddr;
                $this->port = $port;
        }

        public function getIpAddress()
        {
                return $this->ipaddr;
        }

        public function getPort()
        {
                return $this->port;
        }

        //
        // Set object implementing the ProtocolCallback interface.
        //
        public function setCallback($object)
        {
                $this->callback = $object;
        }

        //
        // Opens the TCP connection.
        //
        public function connect()
        {
                $this->sock = fsockopen($this->ipaddr, $this->port, $errno, $errstr);
                if (!$this->sock) {
                        throw new ConnectException($errstr, $errno, $this->ipaddr, $this->port);
                }
        }

        //
        // End TCP connection. This is implicit done when the script execution terminates.
        //
        public function disconnect()
        {
                if ($this->sock) {
                        fclose($this->sock);
                        $this->sock = null;
                }
        }

        //
        // Protocol: send "initilize" command to peer.
        //
        public function initialize()
        {
                if (!isset($this->sock)) {
                        throw new InvalidStateException();
                }
                fwrite($this->sock, "initialize\r\n");
                if (($response = trim(fgets($this->sock))) != "OK") {
                        throw new ProtocolException($response, self::PROTO_INITIALIZE, "initialize");
                }
        }

        //
        // Protocol: send "permit ipaddr" to peer.
        //
        public function permit($ipaddr)
        {
                if (!isset($this->sock)) {
                        throw new InvalidStateException();
                }
                fwrite($this->sock, "permit $ipaddr\r\n");
                if (($response = trim(fgets($this->sock))) != "OK") {
                        throw new ProtocolException($response, self::PROTO_PERMIT, "permit");
                }
        }

        //
        // Protocol: send "restore" to peer.
        //
        public function restore()
        {
                if (!isset($this->sock)) {
                        throw new InvalidStateException();
                }
                fwrite($this->sock, "restore\r\n");
                if (($response = trim(fgets($this->sock))) != "OK") {
                        throw new ProtocolException($response, self::PROTO_RESTORE, "restore");
                }
        }

        //
        // Protocol: send "begin" to peer (start session).
        //
        public function begin()
        {
                if (!isset($this->sock)) {
                        throw new InvalidStateException();
                }
                fwrite($this->sock, "begin\r\n");
                if (($response = trim(fgets($this->sock))) != "OK") {
                        throw new ProtocolException($response, self::PROTO_BEGIN, "begin session");
                }
        }

        //
        // Protocol: send "end" to peer (close session).
        //
        public function end()
        {
                if (!isset($this->sock)) {
                        throw new InvalidStateException();
                }
                fwrite($this->sock, "end\r\n");
                if (($response = trim(fgets($this->sock))) != "OK") {
                        throw new ProtocolException($response, self::PROTO_END, "end session");
                }
        }

        //
        // Authenticate with the peer service.
        //
        private function auth()
        {
                //
                // Deferred extension check because the remote service might
                // not require authentication (that requires the hash extension).
                //
                if (!extension_loaded("hash")) {
                        throw new ProtocolException(
                                "Required hash extension is not loaded",
                                self::PROTO_AUTH,
                                "auth"
                        );
                }

                //
                // Read the auth: nonce string from server:
                // 
                $input = trim(fgets($this->sock));
                $match = array();
                if (preg_match("/AUTH: (.*)/", $input, $match)) {
                        $this->nonce = $match[1];
                } else {
                        $errmsg = sprintf("Expected 'AUTH: nonce', got %s", $input);
                        throw new ProtocolException($errmsg, self::PROTO_AUTH, "auth");
                }

                //
                // Compute the hash checksum of password and nonce using the
                // selected hashing algorithm:
                // 
                $passwd = $this->callback->password($this->ipaddr, $this->port);

                $context = hash_init($this->hash);
                hash_update($context, $passwd);
                hash_update($context, $this->nonce);
                $checksum = hash_final($context);

                //
                // Attempt to logon:
                // 
                fprintf($this->sock, "auth %s\r\n", $checksum);
                if (($response = trim(fgets($this->sock))) != "OK") {
                        throw new ProtocolException($response, self::PROTO_AUTH, "auth");
                }
        }

        //
        // Handshake with remote peer. This method should be the first called
        // after the socket connection has been established.
        //
        public function handshake()
        {
                $greeting = trim(fgets($this->sock));

                //
                // Try to match full greeting, followed by greeting without
                // version information. Fall back to matching without version.
                //
                $match = array();
                if (preg_match("/(\w+) (\d+\.\d+\.\d) \[(\d+\.\d+)\] \(mode: (.*)\)/",
                                $greeting, $match)) {
                        $this->service = $match[1];
                        $this->version = $match[2];
                        $this->proto = $match[3];
                        $this->feature = $match[4];
                } elseif (preg_match("/(\w+) \[\d+\.\d+\] \(mode: (.*)\)/",
                                $greeting, $match)) {
                        $this->service = $match[1];
                        $this->proto = $match[2];
                        $this->feature = $match[3];
                } else {
                        $errmsg = sprintf("Failed interpret greeting (%s)", $greeting);
                        throw new ProtocolException($errmsg, 0, "handshake");
                }

                //
                // Get service characteristics from the feature string.
                //
                $features = explode(",", $this->feature);
                foreach ($features as $feature) {
                        $feature = trim($feature);
                        if ($feature == "master") {
                                $this->master = true;
                        } elseif (strstr($feature, "auth")) {
                                list($this->auth, $this->hash) = explode("=", $feature);
                        } else {
                                $errmsg = sprintf("Unknown feature in mode string: %s", $feature);
                                throw new ProtocolException($errmsg, self::PROTO_GREETING, "handshake");
                        }
                }

                //
                // Logon to server:
                // 
                if ($this->auth) {
                        $this->auth();
                }
        }

        //
        // Helper function extracting hostname from the URL and resolving its
        // IP-addresses. The returned result is an list of ip-addresses or
        // FALSE if the hostname could not be resolved.
        //
        public function resolve($url)
        {
                $pattern = "#^((.*?)://)*([\w\.\d]*)(:(\d+))*(/(.*?))*([?](.*))*$#";
                $match = array();
                if (preg_match($pattern, $url, $match)) {   // hostname is in match index 3
                        $host = $match[3];
                        return gethostbynamel($host);
                }
        }

}

//
// This class represent and manage the locking status for an single host and
// examination. The design goal for this class was to fast answer a simple
// question: "Has a lock been aquired for this remote address and exam?".
//
// The intended use of this class is:
//
//   $locker = new LockerManager($_SERVER['REMOTE_ADDR'], $_REQUEST['exam']);
//   try {
//       if(!$locker->locked()) {
//           $locker->lockdown();
//       }
//   } catch(Exception $exception) {
//       error_log($exception);
//       die(sprintf("Fatal error: %s", $exception->getMessage());
//   }
//
class LockerManager
{

        private $addr;          // The IP-address.
        private $exam;          // The exam ID.
        private $comp;          // The computer ID (see assert).
        private $locked;        // Lock status.

        public function __construct($addr, $exam)
        {
                $this->addr = $addr;
                $this->exam = $exam;
                $this->assert();
        }

        //
        // Returns true if this exam is locked.
        //
        public function locked()
        {
                if (!isset($this->locked)) {
                        $this->refresh();
                }
                return $this->locked;
        }

        //
        // Refresh locked status by query the database.
        //
        public function refresh()
        {
                $this->locked = $this->status();
        }

        // 
        // Lockdown this exam by connecting to the locker service running on
        // the host associated with this exam locker object. If the lock was
        // aquired, update the locked status for this object in the database.
        //
        public function lockdown()
        {
                $service = LockerService::factory($this->addr);
                $service->lockdown($this->exam);

                $this->locked = true;
                $this->flush();
        }

        // 
        // Update the locked status in the database. This is a noop if the
        // locked status hasn't changed.
        // 
        private function flush()
        {
                if ($this->locked != $this->status()) {
                        if ($this->locked) {
                                $sql = sprintf("INSERT INTO locks(computer_id, exam_id) VALUES(%d, %d)",
                                                $this->comp, $this->exam);
                        } else {
                                $sql = sprintf("DELETE FROM locks WHERE computer_id = %d AND exam_id = %d",
                                                $this->comp, $this->exam);
                        }
                        $res = Database::getConnection()->query($sql);
                        if (PEAR::isError($res)) {
                                $this->locked = !$this->locked; // Revert state
                                throw new DatabaseException($res->getMessage());
                        }
                }
        }

        //
        // Returns true if this object has status locked in the database. This
        // function doesn't modify the state of this object.
        //
        private function status()
        {
                $sql = sprintf("SELECT COUNT(*) AS locks
                                FROM locks l, computers c
                                WHERE l.computer_id = c.id AND c.ipaddr = '%s' AND l.exam_id = %d",
                                $this->addr, $this->exam);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();

                return $row['locks'] != 0;
        }

        //
        // Check sanity of database. Throw an exception if no locker service
        // is registered for this host address.
        //
        private function assert()
        {
                $sql = sprintf("SELECT id FROM computers WHERE ipaddr = '%s'", $this->addr);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();
                $this->comp = $row['id'];

                if ($this->comp == 0) {
                        $pubmsg = sprintf(_("The current task requires the lockdown service/daemon (fwexamd) being running on your computer, but no such service has registered itself or been pre-registered for your computer address (%s). Make sure that this service (fwexamd) has been started and is configured to register with this system or contact the system administrator for assistance."), $this->addr);
                        $errmsg = sprintf("The client computer %s has no lockdown service registered in the database.", $this->addr);
                        throw new LockerException($pubmsg, 0, $errmsg);
                }
        }

}

//
// This class provides interaction with the locker service.
// 
class LockerService implements ProtocolCallback
{
        const CREATED = 1;      // Registration created
        const UPDATED = 2;      // Registration updated

        private $pass;          // The locker service password.
        private $addr;          // The locker service IP-address.
        private $port;          // The locker service port number.
        private $host;          // The host address.

        public function __construct($pass, $addr, $port)
        {
                $this->pass = $pass;
                $this->addr = $addr;
                $this->port = $port;
                $this->host = gethostbyaddr($addr);
        }

        //
        // Create the locker service for this exam and host.
        // 
        public static function factory($addr)
        {
                $sql = sprintf("SELECT ipaddr, port, password
                                FROM computers
                                WHERE ipaddr = '%s'", $addr);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();

                $service = new LockerService(
                                $row['password'],
                                $row['ipaddr'],
                                $row['port']);

                return $service;
        }

        //
        // Register this locker service for this host.
        //
        public function register()
        {
                $db = Database::getConnection();

                //
                // Find out whether to create or update:
                //
                $sql = sprintf("SELECT COUNT(*) FROM computers
                                WHERE ipaddr = '%s' AND port = %d", $this->addr, $this->port);
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
                        $sql = sprintf("INSERT INTO computers(hostname, ipaddr, port, password, created)
                                        VALUES('%s','%s',%d,'%s','%s')",
                                        $this->host, $this->addr, $this->port, $this->pass, strftime(DATETIME_DB));
                } else {
                        $sql = sprintf("UPDATE computers SET password = '%s', hostname = '%s'
                                        WHERE ipaddr = '%s' AND port = %d",
                                        $this->pass, $this->host, $this->addr, $this->port);
                }
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }

                return $ret;
        }

        //
        // Call this function to lockdown peer for this exam. Throws LockerException
        // on errors.
        //
        public function lockdown($exam)
        {
                $protocol = new LockerProtocol($this->addr, $this->port);
                $accept = array();

                $questions = Exam::getQuestions($exam);
                foreach ($questions as $question) {
                        //
                        // Collect all required URL's for this question.
                        //
                        $urls = array();
                        if ($question->hasQuestionVideo()) {
                                $urls[] = $question->getQuestionVideo();
                        }
                        if ($question->hasQuestionAudio()) {
                                $urls[] = $question->getQuestionAudio();
                        }
                        if ($question->hasQuestionImage()) {
                                $urls[] = $question->getQuestionImage();
                        }

                        foreach ($urls as $url) {
                                $addresses = $protocol->resolve($url);
                                foreach ($addresses as $address) {
                                        $accept[] = $address;
                                }
                        }
                }

                //
                // Initialize remote peer and pass on list of accepted addresses.
                //
                try {
                        //
                        // Set this object as password callback.
                        // 
                        $protocol->setCallback($this);

                        //
                        // Connection phase including authentication.
                        //
                        $protocol->connect();
                        $protocol->handshake();

                        //
                        // Connected, begin sending configuration commands.
                        //
                        $protocol->begin();
                        $protocol->initialize();
                        foreach ($accept as $ipaddr) {
                                $protocol->permit($ipaddr);
                        }
                        $protocol->end();

                        //
                        // Done, disconnect and close socket.
                        // 
                        $protocol->disconnect();
                } catch (LockerException $exception) {
                        if ($exception instanceof ProtocolException) {
                                $protocol->disconnect();
                        }
                        error_log(print_r($protocol, true));
                        throw $exception;
                }
        }

        //
        // Implement the callback interface.
        //
        public function password($addr, $port)
        {
                return $this->pass;
        }

}

?>
