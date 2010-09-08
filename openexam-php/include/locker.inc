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

    private $ipaddr;       // The peer ip-address
    private $port;         // The destination port
    private $sock = null;  // The TCP socket

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
        fwrite($this->sock, "initialize\n");
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
        fwrite($this->sock, "permit $ipaddr\n");
        if (($response = trim(fgets($this->sock))) != "OK") {
            throw new ProtocolException($response, 0, "permit");
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
        fwrite($this->sock, "restore\n");
        if (($response = trim(fgets($this->sock))) != "OK") {
            throw new ProtocolException($response, 0, "restore");
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
        fwrite($this->sock, "begin\n");
        if (($response = trim(fgets($this->sock))) != "OK") {
            throw new ProtocolException($response, 0, "begin session");
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
        fwrite($this->sock, "end\n");
        if (($response = trim(fgets($this->sock))) != "OK") {
            throw new ProtocolException($response, 0, "end session");
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
class Locker
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
            $this->protocol->connect();
            $this->protocol->begin();
            $this->protocol->initialize();
            foreach ($accept as $ipaddr) {
                $this->protocol->permit($ipaddr);
            }
            $this->protocol->end();
            $this->protocol->disconnect();
        } catch(LockerException $exception) {
            if($exception instanceof ProtocolException) {
                $this->protocol->disconnect();
            }
            throw $exception;
        }
    }

}

?>
