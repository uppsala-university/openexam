<?php

// 
// Copyright (C) 2009-2012 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/ldap.inc
// Author: Anders Lövgren
// Date:   2009-08-27
// 
// LDAP search support.
// 

if (!defined("LDAP_STANDARD_PORT")) {
        define("LDAP_STANDARD_PORT", 389);
}
if (!defined("LDAP_LOOKUP_UID")) {
        define("LDAP_LOOKUP_UID", 1);
}
if (!defined("LDAP_LOOKUP_PRINCIPAL")) {
        define("LDAP_LOOKUP_PRINCIPAL", 2);
}
if (!defined("LDAP_LOOKUP_BOTH")) {
        define("LDAP_LOOKUP_BOTH", 3);
}

if (!defined("LDAP_SEARCH_UID")) {
        define("LDAP_SEARCH_UID", "(uid=%s)");
}
if (!defined("LDAP_SEARCH_SN")) {
        define("LDAP_SEARCH_SN", "(sn=%s)");
}
if (!defined("LDAP_SEARCH_CN")) {
        define("LDAP_SEARCH_CN", "(cn=%s %s)");
}
if (!defined("LDAP_SEARCH_GIVEN_NAME")) {
        define("LDAP_SEARCH_GIVEN_NAME", "(givenname=%s)");
}
if (!defined("LDAP_SEARCH_PERSNR")) {
        define("LDAP_SEARCH_PERSNR", "(norEduPersonNIN=%s)");
}

// 
// Our custom LDAP exception.
// 
class LdapException extends Exception
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
class LdapResultSet implements Iterator
{

        protected $data = array();
        protected $key = null;
        protected $count = 0;
        private $class;

        public function __construct($key, $data, $class = null)
        {
                if (is_array($data)) {
                        $this->data = $data;
                        $this->count = $this->data['count'];
                        unset($this->data['count']);
                } else {
                        $this->data = array($data);
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
                if (isset($this->class)) {
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
class LdapDirectoryEntry extends LdapResultSet
{

        private static $class = "LdapAttributeData";

        public function __construct($key, $data)
        {
                parent::__construct(null, $data, self::$class);
        }

        //
        // Returns an attribute data object for attribute named $name. This
        // allow us to write code as: foreach($entry->mail as $email) { ... }
        //
        public function __get($name)
        {
                return new self::$class($name, $this->data[$name]);
        }

        //
        // Similar to __get($name), but allow us to write code as:
        // if($entry->hasMail()) { $data = $entry->getMail(); ... }
        //
        public function __call($name, $value)
        {
                switch (substr($name, 0, 3)) {
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
class LdapAttributeData extends LdapResultSet
{

        public function __construct($key, $data)
        {
                parent::__construct($key, $data);
        }

        public function __tostring()
        {
                return implode(", ", $this->data);
        }

}

// 
// A simple LDAP query cache.
// 
class LdapResultCache
{

        private $cache = array();

        public function lookup($attr, $str)
        {
                if (isset($this->cache[$attr][$str])) {
                        return $this->cache[$attr][$str];
                } else {
                        return null;
                }
        }

        public function store($attr, $str, $data)
        {
                $this->cache[$attr][$str] = $data;
        }

}

// 
// This class implements the LDAP search functionality. The attribute that's
// returned can be filtered by calling setAttributeFilter().
// 
class LdapConnection
{

        private $basedn;
        private $server;
        private $port = LDAP_STANDARD_PORT;
        private $user = null;    // Optional username (for binding).
        private $pass = null;    // Optional password (for binding).
        private $dc = null;
        private $db = null;
        private static $instance;

        public function __construct($server = null, $basedn = null)
        {
                $this->server = $server;
                $this->basedn = $basedn;
        }

        public function __destruct()
        {
                $this->close();
        }

        public function __get($name)
        {
                return $this->$name;
        }

        public function __set($name, $value)
        {
                $this->$name = $value;
        }

        public function __toString()
        {
                return sprintf("%s@%s:%d/%s", $this->user, $this->server, $this->port, $this->basedn);
        }

        public function connected()
        {
                return $this->dc != null;
        }

        //
        // Explicit open connection with LDAP, otherwise called at first search.
        //
        public function open()
        {
                //
                // Establish connection with the LDAP server. Returns a link
                // resource that we use in subsequent calls.
                //
                if (!($this->dc = ldap_connect($this->server, $this->port))) {
                        throw new LdapException(sprintf(
                            _("Failed connect to LDAP server %s:%d"), $this->server, $this->port));
                }

                //
                // Set LDAP protocol version.
                //
                if (defined("LDAP_PROTOCOL_VERSION")) {
                        if (!ldap_set_option($this->dc, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL_VERSION)) {
                                throw new LdapException(sprintf(
                                    _("Failed set LDAP protocol version %d"), LDAP_PROTOCOL_VERSION));
                        }
                }

                // 
                // Set LDAP search referals. Define LDAP_REFERALS to 0 if ldap_search() fails 
                // with error message 'Operations error' when searching the Active Directory 
                // LDAP without giving an organization unit.
                // 
                if (defined("LDAP_REFERALS")) {
                        if (!ldap_set_option($this->dc, LDAP_OPT_REFERRALS, LDAP_REFERALS)) {
                                throw new LdapException(sprintf(
                                    _("Failed set LDAP search referals to %d"), LDAP_REFERALS));
                        }
                }

                //
                // Try to bind to the directory service.
                //
                $this->db = false;
                if (isset($this->user)) {
                        if (isset($this->pass)) {
                                $this->db = @ldap_bind($this->dc, $this->user, $this->pass);
                        } else {
                                $this->db = @ldap_bind($this->dc, $this->user);
                        }
                } else {
                        $this->db = ldap_bind($this->dc);    // Anonymous
                }
                if (!$this->db) {
                        throw new LdapException(ldap_error($this->dc), ldap_errno($this->dc));
                }
        }

        public function close()
        {
                if (isset($this->dc)) {
                        //
                        // Disconnect from LDAP server:
                        //
                        if (!ldap_unbind($this->dc)) {
                                throw new LdapException(ldap_error($this->dc), ldap_errno($this->dc));
                        } else {
                                $this->dc = null;
                        }
                }
        }

        //
        // Create a LDAP search object based on the settings in conf/config.inc
        //
        public static function create()
        {
                $ldap = new LdapConnection();

                if (defined('LDAP_SERVER')) {
                        $ldap->server = LDAP_SERVER;
                }
                if (defined('LDAP_BASEDN')) {
                        $ldap->basedn = LDAP_BASEDN;
                }
                if (defined('LDAP_PORT')) {
                        $ldap->port = LDAP_PORT;
                }
                if (defined('LDAP_USER')) {
                        $ldap->user = LDAP_USER;
                }
                if (defined('LDAP_PASS')) {
                        $ldap->pass = LDAP_PASS;
                }

                return $ldap;
        }

        // 
        // Get shared LDAP connection instance. A new connection is created if
        // required by calling create().
        // 
        public static function instance()
        {
                if (!isset(self::$instance)) {
                        self::$instance = self::create();
                }
                return self::$instance;
        }

        public function search($filter, $attr = null)
        {
                //
                // Open connection on first search.
                //
                if (!isset($this->dc)) {
                        $this->open();
                }

                //
                // Perform the search against LDAP using the supplied filter:
                //
                if (is_string($attr)) {
                        $attr = array($attr);
                }
                if (isset($attr)) {
                        $rs = ldap_search($this->dc, $this->basedn, $filter, $attr);
                } else {
                        $rs = ldap_search($this->dc, $this->basedn, $filter);
                }
                if (!$rs) {
                        throw new LdapException(ldap_error($this->dc), ldap_errno($this->dc));
                }

                //
                // Get all entries:
                //
                $de = ldap_get_entries($this->dc, $rs);
                if (!$de) {
                        throw new LdapException(ldap_error($this->dc), ldap_errno($this->dc));
                }

                //
                // Return LDAP result object.
                //
                return new LdapResultSet(null, $de, "LdapDirectoryEntry");
        }

}

class LdapSearch
{

        private $connection;
        private $attr = null;    // Search attribute list.
        private $cache;

        /**
         * Constructor.
         * @param LdapConnection $connection The LDAP connection.
         */
        public function __construct($connection)
        {
                $this->connection = $connection;

                if (defined('LDAP_CACHE') && LDAP_CACHE) {
                        $this->cache = new LdapResultCache();
                }
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
        // Set an extern LDAP result cache.
        //
        public function setCache(&$cache)
        {
                $this->cache = $cache;
        }

        /**
         * @return LdapConnection
         */
        public function getConnection()
        {
                return $this->connection;
        }

        //
        // This function performs the search against the LDAP server and returns 
        // a LDAP result object that might contain multiple matched entries.
        // 
        // The $attr parameter works as a tag into the result cache.
        //
        private function search($filter, $attr)
        {
                //
                // Consider cache first:
                //
                if (isset($this->cache)) {
                        $result = $this->cache->lookup($attr, $filter);
                        if (isset($result)) {
                                return $result;
                        }
                }

	        // 
		// Append supplied attribute to attribute filter if set:
		// 
	        if (isset($this->attr)) {
		        if (!is_array($this->attr)) {
			        $this->attr = array($this->attr);
			}
		        if (!in_array($attr, $this->attr)) {
			        $this->attr[] = $attr;
			}
		}
                $result = $this->connection->search($filter, $this->attr);

                if (isset($this->cache)) {
                        $this->cache->store($attr, $filter, $result);
                }
                return $result;
        }

        //
        // Search for LDAP entries matching the common name (CN).
        //
        public function searchCN($firstname, $lastname)
        {
                return $this->search(sprintf(LDAP_SEARCH_CN, $firstname, $lastname), "cn");
        }

        //
        // Search for LDAP entries matching the firstname (givenname).
        //
        public function searchGivenName($firstname)
        {
                return $this->search(sprintf(LDAP_SEARCH_GIVEN_NAME, $firstname), "givenname");
        }

        //
        // Search for LDAP entries matching the lastname (SN).
        //
        public function searchSN($lastname)
        {
                return $this->search(sprintf(LDAP_SEARCH_SN, $lastname), "sn");
        }

        //
        // Search for LDAP entries matching the user ID (UID).
        //
        public function searchUID($account_id)
        {
                return $this->search(sprintf(LDAP_SEARCH_UID, $account_id), "uid");
        }

        //
        // Search for LDAP entries matching the user principal name. The user
        // principal name is the UU-ID with the selected domain appended.
        // This is the function to use when searching for employees.
        //
        public function searchPrincipalName($account_id)
        {
                return $this->search(sprintf(LDAP_SEARCH_PRINCIPAL, $account_id), "principal");
        }

        // 
        // Search for LDAP entries matching the personal number (norEduPersonNIN).
        // 
        public function searchPersNr($persnr)
        {
                if (strlen($persnr) != 12) {
                        $pattern = '/^((\d{6,8})-?(\d{4}))|^((\d{6,8})-?(\d{3}[a-zA-Z]))$/';
                        $replace = '${2}${3}';
                        $persnr = preg_replace($pattern, $replace, $persnr);
                        if (!isset($persnr)) {
                                throw new LdapException("Failed transform personal number.");
                        }
                        if (strlen($persnr) == 10) {
                                if (substr($persnr, 0, 2) > date('y')) {
                                        $persnr = '19' . $persnr;
                                }
                        }
                }

                return $this->search(sprintf(LDAP_SEARCH_PERSNR, $persnr), "norEduPersonNIN");
        }

}

?>
