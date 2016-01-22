<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LdapService.php
// Created: 2014-10-22 04:21:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService {

        use OpenExam\Library\Catalog\DirectoryService\Ldap\LdapConnection;
        use OpenExam\Library\Catalog\DirectoryService\Ldap\LdapResult;
        use OpenExam\Library\Catalog\Exception;
        use OpenExam\Library\Catalog\Group;
        use OpenExam\Library\Catalog\Principal;
        use OpenExam\Library\Catalog\ServiceAdapter;
        use OpenExam\Library\Catalog\ServiceConnection;

        /**
         * LDAP directory service.
         * 
         * This class provides directory service using LDAP as the service backend.
         *
         * @author Anders Lövgren (QNET/BMC CompDept)
         */
        class LdapService extends ServiceAdapter
        {

                /**
                 * The LDAP connection object.
                 * @var LdapConnection 
                 */
                private $ldap;
                /**
                 * Attribute map.
                 * @var array 
                 */
                private $attrmap = array(
                        'person' => array(
                                Principal::ATTR_UID   => 'uid',
                                Principal::ATTR_SN    => 'sn',
                                Principal::ATTR_NAME  => 'cn',
                                Principal::ATTR_GN    => 'givenName',
                                Principal::ATTR_MAIL  => 'mail',
                                Principal::ATTR_PNR   => 'norEduPersonNIN',
                                Principal::ATTR_PN    => 'eduPersonPrincipalName',
                                Principal::ATTR_AFFIL => 'eduPersonAffiliation',
                                Principal::ATTR_ALL   => '*'
                        ),
                        'group'  => array(
                                Group::ATTR_NAME   => 'name',
                                Group::ATTR_DESC   => 'description',
                                Group::ATTR_MEMBER => 'member',
                                Group::ATTR_PARENT => 'memberOf'
                        )
                );
                /**
                 * The search base DN.
                 * @var string 
                 */
                private $basedn;
                /**
                 * The affiliation callback.
                 * @var closure 
                 */
                private $affiliation;

                /**
                 * Constructor.
                 * @param string $host The LDAP server hostname.
                 * @param string $port The LDAP server port.
                 * @param string $user The LDAP bind username.
                 * @param string $pass The LDAP bind password.
                 * @param array $options Array of LDAP_OPT_XXX options.
                 */
                public function __construct($host, $port = 636, $user = null, $pass = null, $options = array())
                {
                        $this->ldap = new LdapConnection($host, $port, $user, $pass, $options);
                        $this->type = 'ldap';
                        $this->affiliation = function($attrs) {
                                return $attrs;
                        };
                }

                /**
                 * Get service connection.
                 * @return ServiceConnection
                 */
                public function getConnection()
                {
                        return $this->ldap;
                }

                /**
                 * Set attribute map.
                 * 
                 * The attribute map can be used to remap the symbolic query attributes
                 * constants defined by the Principal and Group class. The mapped
                 * values are the object class attribute name as defined by the LDAP
                 * schema.
                 * 
                 * <code>
                 * $service->setAttributeMap(array(
                 *      'person' => array(Principal::ATTR_UID => 'sAMAccountName'),
                 *      'group'  => array(Group::ATTR_NAME    => 'cn')
                 * ));
                 * </code>
                 * 
                 * The $attrmap argument is merged with the existing attribute map.
                 * 
                 * @param array $attrmap The attribute map.
                 */
                public function setAttributeMap($attrmap)
                {
                        foreach ($attrmap as $class => $attributes) {
                                $this->attrmap[$class] = array_merge($this->attrmap[$class], $attributes);
                        }
                }

                /**
                 * Get current attribute map.
                 * @return array
                 */
                public function getAttributeMap()
                {
                        return $this->attrmap;
                }

                /**
                 * Set user affiliation callback.
                 * @param callable $callback The callback function.
                 */
                public function setAffiliationCallback($callback)
                {
                        $this->affiliation = $callback;
                }

                /**
                 * Set user affiliation map.
                 * 
                 * Calling this method replaces the current set callback.
                 * 
                 * <code>
                 * $service->setAffiliationMap(array(
                 *      Affiliation::EMPLOYEE => 'employee',
                 *      Affiliation::STUDENT  => 'student'
                 * ));
                 * </code>
                 * 
                 * @param array $affiliation The affiliation map.
                 */
                public function setAffiliationMap($map)
                {
                        $this->affiliation = function($attrs) use($map) {
                                $result = array();
                                foreach ($map as $key => $values) {
                                        if (!is_array($values)) {
                                                $values = array($values);
                                        }
                                        foreach ($values as $val) {
                                                foreach ($attrs as $index => $attr) {
                                                        if ($attr == $val) {
                                                                $result[$key] = true;
                                                        }
                                                }
                                        }
                                }
                                return array_keys($result);
                        };
                }

                /**
                 * Set the search base DN (e.g. DC=example,DC=com).
                 * @param string $basedn
                 */
                public function setBase($basedn)
                {
                        $this->basedn = $basedn;
                }

                /**
                 * Find directory entries.
                 * @param string $type The search attribute (e.g. uid).
                 * @param string $value The search value.
                 * @param array $attributes The attributes to return.
                 * @param string $class The LDAP object class (e.g. user or group). 
                 * @param int $limit Limit on number of records returned.
                 * @return array The directory entries.
                 */
                private function search($type, $value, $attributes, $class = 'person', $limit = null)
                {
                        // 
                        // Return entry from cache if existing:
                        // 
                        if ($this->lifetime) {
                                $cachekey = sprintf("%s-search-%s-%s-%s", $this->name, $class, $type, md5(serialize(array($value, $attributes, $limit))));
                                if ($this->cache->exists($cachekey, $this->lifetime)) {
                                        return $this->cache->get($cachekey, $this->lifetime);
                                }
                        }

                        // 
                        // Select attribute map:
                        // 
                        $attrmap = $this->attrmap[$class];

                        // 
                        // Prepare attribute map:
                        // 
                        $insert = array_diff($attributes, array_keys($attrmap));
                        $remove = array_diff(array_keys($attrmap), $attributes);

                        foreach ($remove as $attribute) {
                                unset($attrmap[$attribute]);
                        }
                        foreach ($insert as $attribute) {
                                $attrmap[$attribute] = $attribute;
                        }

                        // 
                        // Create search filter restricted by object class:
                        // 
                        $filter = sprintf("(&(objectClass=%s)(%s=%s))", $class, $this->attrmap[$class][$type], $value);

                        // 
                        // Search directory tree and return entries:
                        // 
                        if (($result = ldap_search($this->ldap->connection, $this->basedn, $filter, array_values($attrmap), 0, $limit)) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        if (($entries = ldap_get_entries($this->ldap->connection, $result)) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        if (ldap_free_result($result) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        // 
                        // Return entries and attribute map:
                        // 
                        return $this->setCacheData(
                                $cachekey, array('entries' => $entries, 'attrmap' => $attrmap)
                        );
                }

                /**
                 * Read directory entry.
                 * @param string $path The distinguished name.
                 * @param array $attributes The attributes to return.
                 * @param string $class The LDAP object class (e.g. user or group). 
                 * @return array The directory entry data.
                 */
                private function read($path, $attributes, $class = 'person')
                {
                        // 
                        // Return entry from cache if existing:
                        // 
                        if ($this->lifetime) {
                                $cachekey = sprintf("%s-read-%s-%s", $this->name, $class, md5(serialize(array($path, $attributes))));
                                if ($this->cache->exists($cachekey, $this->lifetime)) {
                                        return $this->cache->get($cachekey, $this->lifetime);
                                }
                        }

                        // 
                        // Select attribute map:
                        // 
                        $attrmap = $this->attrmap[$class];

                        // 
                        // Prepare attribute map:
                        // 
                        $insert = array_diff($attributes, array_keys($attrmap));
                        $remove = array_diff(array_keys($attrmap), $attributes);

                        foreach ($remove as $attribute) {
                                unset($attrmap[$attribute]);
                        }
                        foreach ($insert as $attribute) {
                                $attrmap[$attribute] = $attribute;
                        }

                        $filter = sprintf("(objectClass=%s)", $class);

                        // 
                        // Find directory entry:
                        // 
                        if (($result = ldap_read($this->ldap->connection, $path, $filter, array_values($attrmap))) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        if (($entry = ldap_first_entry($this->ldap->connection, $result)) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        if (($data = ldap_get_attributes($this->ldap->connection, $entry)) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        if (ldap_free_result($result) == false) {
                                throw new Exception(ldap_error($this->ldap->connection), ldap_errno($this->ldap->connection));
                        }

                        // 
                        // Add reference for future use:
                        // 
                        $data['dn'] = $path;

                        // 
                        // Make compatible with search result (lower case attributes):
                        // 
                        $data = array_change_key_case($data);
                        foreach ($data as $index => $val) {
                                if (is_numeric($index) && is_string($val)) {
                                        $data[$index] = strtolower($val);
                                }
                        }

                        /*
                         * printf("%s:%d %s\n", __METHOD__, __LINE__, print_r($data, true)); 
                         */

                        // 
                        // Return entry data and attribute map:
                        //                         
                        return $this->setCacheData(
                                $cachekey, array('entry' => $data, 'attrmap' => $attrmap)
                        );
                }

                /**
                 * Get attribute (Principal::ATTR_XXX) for user.
                 * 
                 * <code>
                 * // Get all email addresses:
                 * $service->getAttribute('user@example.com', Principal::ATTR_MAIL);
                 * 
                 * // Get user given name:
                 * $service->getAttribute('user@example.com', Principal::ATTR_GN);
                 * </code>
                 * 
                 * @param string $principal The user principal name.
                 * @param string $attribute The attribute to return.
                 * @return array
                 */
                public function getAttribute($principal, $attribute)
                {
                        // 
                        // Return entry from cache if existing:
                        // 
                        if ($this->lifetime) {
                                $cachekey = sprintf("%s-attribute-%s-%s", $this->name, $attribute, md5($principal));
                                if ($this->cache->exists($cachekey, $this->lifetime)) {
                                        return $this->cache->get($cachekey, $this->lifetime);
                                }
                        }

                        $search = $this->search(Principal::ATTR_PN, $principal, array($attribute));

                        $result = new LdapResult(array_flip($search['attrmap']));
                        $result->setName($this->name);
                        $result->insert($search['entries']);

                        $output = $result->getResult();
                        if ($attribute == Principal::ATTR_AFFIL) {
                                $affilation = $this->affiliation;
                                foreach ($output as $index => $array) {
                                        $output[$index][$attribute] = $affilation($array[$attribute]);
                                }
                        }

                        // 
                        // Filter out related entries not containing the
                        // requested attribute:
                        // 
                        return $this->setCacheData(
                                $cachekey, array_filter($output, function($entry) use($attribute) {
                                        return isset($entry[$attribute]);
                                })
                        );
                }

                /**
                 * Get groups for user.
                 * @param string $principal The user principal name.
                 * @param array $attributes The attributes to return.
                 * @return array
                 */
                public function getGroups($principal, $attributes)
                {
                        // 
                        // Return entry from cache if existing:
                        // 
                        if ($this->lifetime) {
                                $cachekey = sprintf("%s-groups-%s", $this->name, md5(serialize(array($principal, $attributes))));
                                if ($this->cache->exists($cachekey, $this->lifetime)) {
                                        return $this->cache->get($cachekey, $this->lifetime);
                                }
                        }

                        // 
                        // Get distinguished names for all user principal groups:
                        // 
                        $member = strtolower($this->attrmap['group'][Group::ATTR_PARENT]);
                        $mapped = $this->getAttribute($principal, $member);
                        $groups = array();

                        // 
                        // Missing member attributes in LDAP:
                        // 
                        if (!isset($mapped[0][$member])) {
                                return array();
                        }

                        // 
                        // Fetch group data from LDAP:
                        // 
                        foreach ($mapped as $data) {
                                foreach ($data[$member] as $group) {
                                        $search = $this->read($group, $attributes, 'group');
                                        $groups[] = $search['entry'];
                                }
                        }
                        $groups['count'] = count($groups);

                        // 
                        // Collect group data in result object:
                        // 
                        $result = new LdapResult(array_flip($search['attrmap']));
                        $result->setName($this->name);
                        $result->append($groups);

                        return $this->setCacheData($cachekey, $result->getResult());
                }

                /**
                 * Get user principal object.
                 * 
                 * <code>
                 * // Search three first Tomas in example.com domain:
                 * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
                 * 
                 * // Get email for user tomas:
                 * $manager->getPrincipal('thomas@example.com', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
                 * 
                 * // Get email for user principal tomas@example.com:
                 * $manager->getPrincipal('thomas@example.com', Principal::ATTR_PN, array('attr' => Principal::ATTR_MAIL));
                 * </code>
                 * 
                 * The $options parameter is an array containing zero or more of 
                 * these fields:
                 * 
                 * <code>
                 * array(
                 *       'attr'   => array(),   // attributes to return
                 *       'limit'  => 0,         // limit number of entries
                 *       'domain' => null,      // restrict to domain
                 *       'data'   => true       // append search data in attr member
                 * )
                 * </code>
                 * 
                 * @param string $needle The attribute search string.
                 * @param string $attribute The attribute to query.
                 * @param array $options Various search options.
                 * @return Principal[] Matching user principal objects.
                 */
                public function getPrincipal($needle, $attribute, $options)
                {
                        // 
                        // Return entry from cache if existing:
                        // 
                        if ($this->lifetime) {
                                $cachekey = sprintf("%s-principal-%s-%s", $this->name, $attribute, md5(serialize(array($needle, $options))));
                                if ($this->cache->exists($cachekey, $this->lifetime)) {
                                        return $this->cache->get($cachekey, $this->lifetime);
                                }
                        }

                        // 
                        // Search for attribute matching needle:
                        // 
                        $search = $this->search($attribute, $needle, $options['attr'], 'person', $options['limit']);

                        // 
                        // Collect group data in result object:
                        // 
                        $result = new LdapResult(array_flip($search['attrmap']));
                        $result->setName($this->name);
                        $result->insert($search['entries']);
                        $data = $result->getResult();

                        // 
                        // Create user principal objects:
                        // 
                        $principals = array();
                        foreach ($data as $d) {
                                $principal = new Principal();

                                // 
                                // Populate public properties in principal object:
                                // 
                                foreach ($d as $attr => $attrs) {
                                        if (property_exists($principal, $attr)) {
                                                if ($attr == Principal::ATTR_MAIL) {
                                                        $principal->mail = $attrs;
                                                        unset($d[$attr]);
                                                } elseif ($attr == Principal::ATTR_AFFIL) {
                                                        $affilation = $this->affiliation;
                                                        $principal->affiliation = $affilation($attrs);
                                                        unset($d[$attr]);
                                                } else {
                                                        $principal->$attr = $attrs[0];
                                                        unset($d[$attr]);
                                                }
                                        }
                                }

                                // 
                                // Any left over attributes goes in attr member:
                                // 
                                if ($options) {
                                        $principal->attr = $d;
                                } else {
                                        $principal->attr['svc'] = $d['svc'];
                                }

                                $principals[] = $principal;
                        }

                        // 
                        // Return user principals:
                        // 
                        return $this->setCacheData($cachekey, $principals);
                }

                /**
                 * Get members of group.
                 * @param string $group The group name.
                 * @param string $domain Restrict search to domain.
                 * @param array $attributes The attributes to return.
                 * @return Principal[]
                 */
                public function getMembers($group, $domain, $attributes)
                {
                        // 
                        // Return entry from cache if existing:
                        // 
                        if ($this->lifetime) {
                                $cachekey = sprintf("%s-members-%s", $this->name, md5(serialize(array($group, $domain, $attributes))));
                                if ($this->cache->exists($cachekey, $this->lifetime)) {
                                        return $this->cache->get($cachekey, $this->lifetime);
                                }
                        }

                        // 
                        // Search in group member attribute:
                        // 
                        $member = $this->attrmap['group'][Group::ATTR_MEMBER];
                        $search = $this->search(Group::ATTR_NAME, $group, array($member), 'group');
                        $users = array();

                        // 
                        // Load members into result:
                        // 
                        $result = new LdapResult(array_flip($search['attrmap']));
                        $result->setName($this->name);
                        $result->insert($search['entries']);
                        $data = $result->getResult();

                        // 
                        // This group has no members:
                        // 
                        if ($result->count() == 0) {
                                return array();
                        }

                        // 
                        // Fetch user data from LDAP:
                        // 
                        foreach ($data as $d) {
                                foreach ($d[$member] as $path) {
                                        $user = $this->read($path, $attributes);
                                        $users[] = $user['entry'];
                                }
                        }
                        $users['count'] = count($users);

                        // 
                        // Insert user data in result:
                        // 
                        $result->replace($users, array_flip($user['attrmap']));
                        $data = $result->getResult();

                        // 
                        // Create user principal objects:
                        // 
                        $principals = array();
                        foreach ($data as $d) {
                                $principal = new Principal();

                                // 
                                // Populate public properties in principal object:
                                // 
                                foreach ($d as $attr => $attrs) {
                                        if (property_exists($principal, $attr)) {
                                                if ($attr == Principal::ATTR_MAIL) {
                                                        $principal->mail = $attrs;
                                                        unset($d[$attr]);
                                                } else {
                                                        $principal->$attr = $attrs[0];
                                                        unset($d[$attr]);
                                                }
                                        }
                                }

                                // 
                                // Any left over attributes goes in attr member:
                                // 
                                $principal->attr = $d;

                                $principals[] = $principal;
                        }

                        // 
                        // Return user principals:
                        // 
                        return $this->setCacheData($cachekey, $principals);
                }

        }

}

namespace OpenExam\Library\Catalog\DirectoryService\Ldap {

        use OpenExam\Library\Catalog\Exception;
        use OpenExam\Library\Catalog\ServiceConnection;

        /**
         * LDAP server connection class.
         * 
         * @property-read resource $connection The LDAP server connection.
         * @author Anders Lövgren (QNET/BMC CompDept)
         */
        class LdapConnection implements ServiceConnection
        {

                /**
                 * The LDAP connection.
                 * @var resource 
                 */
                private $ldap;
                /**
                 * The LDAP server hostname.
                 * @var string 
                 */
                private $host;
                /**
                 * The LDAP server port.
                 * @var int 
                 */
                private $port;
                /**
                 * The LDAP bind username.
                 * @var string 
                 */
                private $user;
                /**
                 * The LDAP bind password.
                 * @var string 
                 */
                private $pass;
                /**
                 * LDAP_OPT_XXX options.
                 * @var array 
                 */
                private $options;

                /**
                 * Constructor.
                 * @param string $host The LDAP server hostname.
                 * @param string $port The LDAP server port.
                 * @param string $user The LDAP bind username.
                 * @param string $pass The LDAP bind password.
                 * @param array $options Array of LDAP_OPT_XXX options.
                 */
                public function __construct($host, $port, $user, $pass, $options)
                {
                        $this->host = $host;
                        $this->port = $port;
                        $this->user = $user;
                        $this->pass = $pass;
                        $this->options = $options;
                }

                public function __get($name)
                {
                        if ($name == 'connection') {
                                return $this->getConnection();
                        }
                }

                /**
                 * Opens connection on-demand.
                 */
                public function getConnection()
                {
                        if (!$this->connected()) {
                                $this->open();
                        }
                        return $this->ldap;
                }

                /**
                 * Set LDAP connection option.
                 * @param string $name The LDAP_OPT_XXX constant.
                 * @param mixed $value The option value.
                 */
                public function setOption($name, $value)
                {
                        $this->options[$name] = $value;
                }

                /**
                 * Open connection to LDAP server.
                 */
                public function open()
                {
                        if (($this->ldap = ldap_connect($this->host, $this->port)) == false) {
                                throw new Exception(sprintf(
                                    "Failed connect to LDAP server %s:%d", $this->host, $this->port
                                ));
                        }

                        foreach ($this->options as $name => $value) {
                                if (ldap_set_option($this->ldap, $name, $value) == false) {
                                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                                }
                        }

                        if (@ldap_bind($this->ldap, $this->user, $this->pass) == false) {
                                throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                        }
                }

                /**
                 * Close connection to LDAP server.
                 */
                public function close()
                {
                        if (ldap_unbind($this->ldap) == false) {
                                throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                        }
                }

                /**
                 * Check if connected to LDAP server.
                 * @return bool
                 */
                public function connected()
                {
                        return is_resource($this->ldap);
                }

        }

        /**
         * LDAP result class.
         */
        class LdapResult
        {

                /**
                 * The reverse attribute map.
                 * @var array 
                 */
                private $attrmap;
                /**
                 * The result array.
                 * @var array 
                 */
                private $result = array();
                /**
                 * The service name.
                 * @var type 
                 */
                private $name;
                /**
                 * The service type.
                 */
                private $type;

                /**
                 * Constructor.
                 * @param array $attrmap The reverse attribute map.
                 * @param string $name The service name.
                 * @param string $type The service type.
                 */
                public function __construct($attrmap, $name = null, $type = 'ldap')
                {
                        $this->attrmap = array_change_key_case($attrmap);
                        $this->name = $name;
                        $this->type = $type;
                }

                /**
                 * Set service name.
                 * @param string $name The service name.
                 */
                public function setName($name)
                {
                        $this->name = $name;
                }

                /**
                 * Set service type.
                 * @param string $type The service type.
                 */
                public function setType($type)
                {
                        $this->type = $type;
                }

                /**
                 * Get result array.
                 * @return array
                 */
                public function getResult()
                {
                        return $this->result;
                }

                /**
                 * Returns the number of records in result.
                 * @return int
                 */
                public function count()
                {
                        return count($this->result);
                }

                /**
                 * Reset internal state.
                 * @param array $attrmap The reverse attribute map.
                 */
                public function reset($attrmap)
                {
                        $this->result = array();
                        $this->attrmap = array_change_key_case($attrmap);
                }

                /**
                 * Append directory entries.
                 * @param array $entries The directory entries.
                 */
                public function append($entries)
                {
                        for ($i = 0; $i < $entries['count']; $i++) {
                                $this->addEntry($i, $entries[$i]);
                        }
                }

                /**
                 * Insert directory entries.
                 * @param array $entries The directory entries.
                 */
                public function insert($entries)
                {
                        $this->result = array();
                        $this->append($entries);
                }

                /**
                 * Replace directory entries.
                 * @param array $entries The directory entries.
                 * @param array $attrmap The reverse attribute map.
                 */
                public function replace($entries, $attrmap)
                {
                        $this->reset($attrmap);
                        $this->append($entries);
                }

                /**
                 * Add directory entry.
                 * @param array $entry
                 */
                private function addEntry($index, $entry)
                {
                        // 
                        // Set service data reference:
                        // 
                        $this->result[$index]['svc'] = array(
                                'name' => $this->name,
                                'type' => $this->type,
                                'ref'  => $entry['dn']
                        );
                        // 
                        // Polulate entry data:
                        // 
                        for ($i = 0; $i < $entry['count']; $i++) {
                                $name = $entry[$i];
                                $attr = $entry[$name];
                                $this->addAttribute($index, $name, $attr);
                        }
                }

                /**
                 * Add attribute values at index $index.
                 * @param string|int $index The containing index.
                 * @param string $name The attribute name.
                 * @param array $attr The attribute value.
                 */
                private function addAttribute($index, $name, $attr)
                {
                        // 
                        // Split on attribute name/extension:
                        // 
                        if (strpos($name, ';')) {
                                list($name, $type) = explode(";", $name);
                        }

                        // 
                        // Get reverse mapped attribute name:
                        // 
                        if (isset($this->attrmap[$name])) {
                                $name = $this->attrmap[$name];
                        }

                        // 
                        // Add attribute data to result:
                        // 
                        for ($i = 0; $i < $attr['count']; $i++) {
                                if (!isset($this->result[$index][$name])) {
                                        $this->result[$index][$name] = array();
                                }
                                if (!in_array($attr[$i], $this->result[$index][$name])) {
                                        $this->result[$index][$name][] = $attr[$i];
                                }
                                if (isset($type)) {
                                        $this->result[$index][$name][$type] = $attr[$i];
                                }
                        }
                }

        }

}
