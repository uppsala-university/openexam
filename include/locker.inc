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
    private $ipaddr;   // The peer ip-address
    
    public function __construct($ipaddr)
    {
	$this->ipaddr = $ipadddr;
    }
    
    // 
    // Send "initilize" command to peer.
    // 
    public function initialize()
    {
	// TODO: implement
    }
    
    // 
    // Send "permit ipaddr" to peer.
    // 
    public function permit($ipaddr) 
    {
	// TODO: implement
    }
    
    // 
    // Send "release" to peer.
    // 
    public function release()
    {
	// TODO: implement
    }
    
    // 
    // An helper function for resolving the ip-address from an URL.
    // 
    public function resolve($url)
    {
	// TODO: implement
    }
}

?>
