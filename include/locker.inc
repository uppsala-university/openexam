<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/locker.inc
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// This class communicates with the locker daemon/service running on each 
// computer that participates in the online exam.
// 
// The purpose of the locker daemon on the client computer is to configure
// the local firewall to only accept outgoing connections to those resources
// (actually URL:s to i.e. a video clip). 
// 
// The resources (URL:s) are defined along with the questions in our database.
// The lockdown protocol is really simple, it consists of plain text messages
// like this:
// 
//   "initialize"      // Perform initial lockdown, open firewall to common resources.
//   "permit ipaddr"   // Permit access to this ip-address.
//   "restore"         // Restore the computer state to pre-initialize state.
// 
// All commands should be terminated by a newline. The client responds with
// an "OK" or "ERROR: message".
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
                $pubmsg = _("Failed connect to lock down service. Please check that the fwexamd daemon/service is running on your computer.");

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
                $this->ipaddr = $ipadddr;
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
                $this->sock = fsockopen($ipaddr, $port, $errno, $errstr);
                if (!$this->sock) {
                        throw new ConnectException($errstr, $errno, $ipaddr, $port);
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
                $passwd = $this->callback->password();

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
        // An helper function for resolving ip-addresses from an URL. The returned
        // result is an list of ip-addresses or FALSE if hostname could not be resolved.
        //
        public function resolve($url)
        {
                $pattern = "/^(http|https|ftp|ftps|sftp|rtsp|mms):\/\/(.*?)\/(.*)\/(.*?)?([?].*)?$/";
                $match = array();
                if (preg_match($pattern, $url, $match)) {   // host is match index 2
                        $host = $match[2];
                        return gethostbynamel($host);
                }
        }

}

//
// This class provides the locker function for clients.
//
class Locker implements ProtocolCallback
{

        private $protocol;
        private $exam;

        public function __construct($exam)
        {
                $this->protocol = new LockerProtocol($_SERVER['REMOTE_ADDR'], FWEXAMD_PORT);
                $this->exam = $exam;
        }

        //
        // Call this function to lockdown peer for this exam. Throws LockerException
        // on errors.
        //
        public function lockdown()
        {
                $accept = array();

                $questions = Exam::getQuestions($this->exam);
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
                                $addresses = $this->protocol->resolve($url);
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
                        $this->protocol->setCallback($this);
                        
                        //
                        // Connection phase including authentication.
                        //
                        $this->protocol->connect();
                        $this->protocol->handshake();

                        //
                        // Connected, begin sending configuration commands.
                        //
                        $this->protocol->begin();
                        $this->protocol->initialize();
                        foreach ($accept as $ipaddr) {
                                $this->protocol->permit($ipaddr);
                        }
                        $this->protocol->end();

                        //
                        // Done, disconnect and close socket.
                        // 
                        $this->protocol->disconnect();
                } catch (LockerException $exception) {
                        if ($exception instanceof ProtocolException) {
                                $this->protocol->disconnect();
                        }
                        throw $exception;
                }
        }

        // 
        // Implements the interface.
        //
        public function password($ipaddr, $port)
        {
                $sql = sprintf("SELECT password FROM computers
                                WHERE ipaddr = '%s' AND port = %d", $ipaddr, $port);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();
                return $row[0];
        }

}

?>
