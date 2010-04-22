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

if(!defined("FWEXAMD_PORT")) {
    define ("FWEXAMD_PORT", 3751);
}

// 
// The exception thrown upon errors.
// 
class LockerException extends Exception
{
    private $userinfo;  // Extended information, not for public.
    
    function __construct($message, $code = 0, $userinfo = null)
    {
	parent::__construct($message, $code);
	$this->userinfo = $userinfo;
    }
    
    public function getUserInfo()
    {
	return $this->userinfo;
    }
    
    function __toString()
    {
	return $this->message;
    }
}

// 
// The locker class implementing the lockdown protocol.
// 
class Locker
{
    private $ipaddr;          // The peer ip-address
    private $port;            // The destination port
    private $sock = null;     // The TCP socket

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
	if(!$this->sock) {
	    throw new LockerException($errstr, $errno, "Failed connect to the locker daemon on $ipaddr.");
	}
    }
    
    // 
    // End TCP connection. This is implicit done when the script execution terminates.
    // 
    public function disconnect()
    {
	if($this->sock) {
	    fclose($this->sock);
	    $this->sock = null;
	}
    }
	  
    // 
    // Protocol: send "initilize" command to peer.
    // 
    public function initialize()
    {
	if(!isset($this->sock)) {
	    throw new LockerException($errstr, $errno, "The TCP socket is not connected to peer.");
	}
	fwrite($this->sock, "initialize\n");
	if(($response = trim(fgets($this->sock))) != "OK") {
	    throw new LockerException($response, 0, "Failed initialize lockdown on peer computer.");
	}
    }
    
    // 
    // Protocol: send "permit ipaddr" to peer.
    // 
    public function permit($ipaddr) 
    {
	if(!isset($this->sock)) {
	    throw new LockerException($errstr, $errno, "The TCP socket is not connected to peer.");
	}
	fwrite($this->sock, "permit $ipaddr\n");
	if(($response = trim(fgets($this->sock))) != "OK") {
	    throw new LockerException($response, 0, "Failed add permit rule.");
	}
    }
    
    // 
    // Protocol: send "release" to peer.
    // 
    public function release()
    {
	if(!isset($this->sock)) {
	    throw new LockerException($errstr, $errno, "The TCP socket is not connected to peer.");
	}
	fwrite($this->sock, "restore\n");
	if(($response = trim(fgets($this->sock))) != "OK") {
	    throw new LockerException($response, 0, "Failed restore lockdown state on peer computer.");
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
	if(preg_match($pattern, $url, $match)) {   // host is match index 2
	    $host = $match[2];
	    return gethostbynamel($host);
	}
    }
}

?>
