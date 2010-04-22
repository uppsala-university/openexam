<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/ldap.inc
// Author: Anders Lövgren
// Date:   2009-08-27
// 
// LDAP search support.
// 

define("LDAP_STANDARD_PORT", 389);

// 
// Our custom LDAP exception.
// 
class LDAPException extends Exception
{
    function __construct($message, $code = 0) 
    {
	parent::__construct($message, $code);
    }
    
    function __toString() 
    {
	return $this->message;
    }
}

// 
// The LDAP result set of entries. Note that this is actually a collection.
// 
class LDAPResultSet implements Iterator
{
    protected $data = array();
    protected $key = null;
    protected $count = 0;
    private $class;

    public function __construct($key, $data, $class = null)
    {
	if(is_array($data)) {
	    $this->data = $data;
	    $this->count = $this->data['count'];
	    unset($this->data['count']);
	} else {
	    $this->data = array( $data );
	    $this->count = 1;
	}
	$this->key = $key;
	$this->class = $class;
    }
    
    public function rewind() 
    {
	reset($this->data);
    }
    
    private function node($key, $data)
    {
	if(isset($this->class)) {
	    return new $this->class($key, $data);
	} else {
	    return $data;
	}
    }
    
    public function index($pos)
    {
	return self::node(key($this->data[$pos]), $this->data[$pos]);
    }

    public function first()
    {
	$data = reset($this->data);
	return self::node(key($this->data), $data);
    }

    public function last()
    {
	$data = end($this->data);
	return self::node(key($this->data), $data);
    }
    
    public function prev()
    {
	$data = prev($this->data);
	return self::node(key($this->data), $data);
    }
    
    public function next() 
    {
	$data = next($this->data);
	return self::node(key($this->data), $data);
    }
    
    public function current() 
    {
	$data = current($this->data);
	return self::node(key($this->data), $data);
    }
    
    public function key() 
    {
	return $this->key;
    }
    
    public function valid() 
    {
	return current($this->data) !== false;
    }
    
    public function count() 
    {
	return $this->count;
    }
}

// 
// Represent a single directory entry. This is also a collection of 
// attributes.
// 
class LDAPDirectoryEntry extends LDAPResultSet
{
    private static $class = "LDAPAttributeData";
    
    public function __construct($key, $data)
    {
	parent::__construct(null, $data, self::$class);
    }

    // 
    // Returns an attribute data object for attribute named $name. This
    // allow us to write code as: foreach($entry->mail as $email) { ... }
    // 
    private function __get($name)
    {
	return new self::$class($name, $this->data[$name]);
    }

    // 
    // Similar to __get($name), but allow us to write code as:
    // if($entry->hasMail()) { $data = $entry->getMail(); ... }
    // 
    private function __call($name, $value)
    {
	switch(substr($name, 0, 3)) {
	 case "get":
	    $attr = substr(strtolower($name), 3);
	    return new self::$class($attr, $this->data[$attr]);
	 case "has":
	    $attr = substr(strtolower($name), 3);
	    return isset($this->data[$attr]);
	}
    }
}

// 
// Represent the collection of attribute values for a single 
// directory entry attribute.
// 
class LDAPAttributeData extends LDAPResultSet
{
    public function __construct($key, $data)
    {
	parent::__construct($key, $data);
    }
    
    private function __tostring()
    {
	return implode(", ", $this->data);
    }
}

// 
// This class implements the LDAP search functionality. The attribute that's
// returned can be filtered by calling setAttributeFilter().
// 
class LDAPSearch
{
    private $basedn;
    private $server;
    private $port = LDAP_STANDARD_PORT;
    
    private $user = null;    // Optional username (for binding).
    private $pass = null;    // Optional password (for binding).
    
    private $attr = null;    // Search attribute list.
    
    public function __construct($server = null, $basedn = null)
    {
	$this->server = $server;
	$this->basedn = $basedn;
    }
    
    // 
    // Create a LDAP search object based on the settings in conf/config.inc
    // 
    public static function factory()
    {
	$ldap = new LDAPSearch();
	
	if(defined('LDAP_SERVER')) {
	    $ldap->setServer(LDAP_SERVER);
	}
	if(defined('LDAP_BASEDN')) {
	    $ldap->setBaseDN(LDAP_BASEDN);
	}
	if(defined('LDAP_PORT')) {
	    $ldap->setPort(LDAP_PORT);
	}
	if(defined('LDAP_USER')) {
	    $ldap->setUserName(LDAP_USER);
	}
	if(defined('LDAP_PASS')) {
	    $ldap->setPassword(LDAP_PASS);
	}
	
	return $ldap;
    }
    
    public function setBaseDN($basedn)
    {
	$this->basedn = $basedn;
    }
    
    public function getBaseDN()
    {
	return $this->basedn;
    }
    
    public function setServer($server)
    {
	$this->server = $server;
    }
    
    public function getServer()
    {
	return $this->server;
    }
    
    public function setPort($port)
    {
	$this->port = $port;
    }
    
    public function getPort()
    {
	return $this->port;
    }
    
    public function setPassword($pass)
    {
	$this->pass = $pass;
    }
    
    public function setUserName($user)
    {
	$this->user = $user;
    }
    
    public function getUserName()
    {
	return $this->user;
    }

    // 
    // Set the attribute filter for following LDAP searches. Pass
    // null to clear all filtering of attributes.
    // 
    public function setAttributeFilter($attr)
    {
	$this->attr = $attr;
    }
    
    // 
    // This function performs the search against the LDAP server
    // and returns a LDAP result object that might contain multiple
    // matched entries.
    // 
    private function search($filter)
    {	
	// 
	// Establish connection with the LDAP server. Returns a link
	// resource that we use in subsequent calls.
	// 
	$dc = ldap_connect($this->server, $this->port);
	if(!$dc) {
	    throw new LDAPException(sprintf(_("Failed connect to LDAP server %s:%d"),
					    $this->server, $this->port));
	}
	
	// 
	// Set LDAP protocol version.
	// 
	if(defined("LDAP_PROTOCOL_VERSION")) {
	    if(!ldap_set_option($dc, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL_VERSION)) {
		throw new LDAPException(sprintf(_("Failed set LDAP protocol version %d"), 
						LDAP_PROTOCOL_VERSION));
	    }
	}
	
	// 
	// Try to bind to the directory service.
	// 
	$db = false;
	if(isset($this->user)) {
	    if(isset($this->pass)) {
		$db = @ldap_bind($dc, $this->user, $this->pass);
	    } else {
		$db = @ldap_bind($dc, $this->user);
	    }
	} else {
	    $db = ldap_bind($dc);    // Anonymous
	}
	if(!$db) {
	    throw new LDAPException(ldap_error($dc), ldap_errno($dc));
	}

	// 
	// Perform the search against using the supplied filter:
	// 
	if(isset($this->attr)) {
	    $rs = ldap_search($dc, $this->basedn, $filter, $this->attr);
	} else {
	    $rs = ldap_search($dc, $this->basedn, $filter);
	}
	if(!$rs) {
	    throw new LDAPException(ldap_error($dc), ldap_errno($dc));
	}
	
	// 
	// Get all entries:
	// 
	$de = ldap_get_entries($dc, $rs);	
	if(!$de) {
	    throw new LDAPException(ldap_error($dc), ldap_errno($dc));
	}
	
	// 
	// Disconnect from LDAP server:
	// 
	if(!ldap_unbind($dc)) {
	    throw new LDAPException(ldap_error($dc), ldap_errno($dc));
	}
	
	// 
	// Return LDAP result object.
	// 
	return new LDAPResultSet(null, $de, "LDAPDirectoryEntry");
    }
    
    // 
    // Search for LDAP entries matching the common name (CN).
    // 
    public function searchCN($firstname, $lastname)
    {
	return self::search(sprintf("(cn=%s %s)", $firstname, $lastname));
    }

    // 
    // Search for LDAP entries matching the firstname (givenname).
    // 
    public function searchGivenName($firstname)
    {
	return self::search(sprintf("(givenname=%s)", $firstname));
    }
    
    // 
    // Search for LDAP entries matching the lastname (SN).
    // 
    public function searchSN($lastname)
    {
	return self::search(sprintf("(sn=%s)", $lastname));
    }
    
    // 
    // Search for LDAP entries matching the user ID (UID).
    // 
    public function searchUID($account_id) 
    {
	return self::search(sprintf("(uid=%s)", $account_id));
    }    
    
    // 
    // Search for LDAP entries matching the user principal name. The user 
    // principal name is the UU-ID with the domain anst.uu.se appended. 
    // This is the function to use when searching for employees.
    // 
    public function searchPrincipalName($account_id)
    {
	return self::search(sprintf("(edupersonprincipalname=%s@anst.uu.se)", $account_id));
    }
}

?>
