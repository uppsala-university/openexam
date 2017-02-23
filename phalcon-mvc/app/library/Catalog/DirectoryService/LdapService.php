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

namespace OpenExam\Library\Catalog\DirectoryService;

use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\DirectoryService\Ldap\Connection;
use OpenExam\Library\Catalog\DirectoryService\Ldap\Result;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Group;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Catalog\ServiceConnection;

/**
 * LDAP directory service.
 * 
 * This class provides directory service using LDAP as the service backend.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class LdapService extends AttributeService
{

        /**
         * The LDAP connection object.
         * @var Connection 
         */
        private $_conn;
        /**
         * The search base DN.
         * @var string 
         */
        private $_base;
        /**
         * The primary attribute value.
         * @var string 
         */
        private $_primary = DirectoryService::PRIMARY_ATTR_VALUE;
        /**
         * The primary language value.
         * @var string 
         */
        private $_language = DirectoryService::PRIMARY_LANG_ENGLISH;

        /**
         * Constructor.
         * @param Connection $connection The LDAP service connection.
         */
        public function __construct($connection)
        {
                $this->_conn = $connection;
                $this->_type = 'ldap';

                parent::__construct(array(
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
                ));
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_base);
                parent::__destruct();
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return $this->_conn;
        }

        /**
         * Set the search base DN (e.g. DC=example,DC=com).
         * @param string $basedn
         */
        public function setBase($basedn)
        {
                $this->_base = $basedn;
        }

        /**
         * Set primary attribute value.
         * @param string $attr The primary attribute value.
         */
        public function setPrimaryAttribute($attr)
        {
                $this->_primary = $attr;
        }

        /**
         * Set prefered language (i.e. lang-sv).
         * @param string $attr The primary language value.
         */
        public function setPrimaryLanguage($attr)
        {
                $this->_language = $attr;
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
        private function search($type, $value, $attributes, $class = 'person', $limit = 0)
        {
                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-search-%s-%s-%s", $this->_name, $class, $type, md5(serialize(array($value, $attributes, $limit))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                // 
                // Return all attributes if unset:
                // 
                if (!isset($attributes)) {
                        $attributes = array();
                }
                if ($attributes == false) {
                        $attributes = array();
                }
                if ($attributes == '*') {
                        $attributes = array();
                }
                if (is_string($attributes)) {
                        $attributes = array($attributes);
                }
                if (isset($attributes[0]) && $attributes[0] == '*') {
                        $attributes = array();
                }
                if (count($attributes) == 0) {
                        $attributes = array_keys($this->_attrmap[$class]);
                }

                // 
                // Select attribute map:
                // 
                $attrmap = $this->_attrmap[$class];

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
                $filter = sprintf("(&(objectClass=%s)(%s=%s))", $class, $this->_attrmap[$class][$type], $value);

                // 
                // Search directory tree and return entries:
                // 
                if (($result = @ldap_search($this->_conn->handle, $this->_base, $filter, array_values($attrmap), 0, $limit)) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
                }

                if (($entries = ldap_get_entries($this->_conn->handle, $result)) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
                }

                if (ldap_free_result($result) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
                }

                // 
                // Return entries and attribute map:
                // 
                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, array('entries' => $entries, 'attrmap' => $attrmap));
                } else {
                        return array('entries' => $entries, 'attrmap' => $attrmap);
                }
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
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-read-%s-%s", $this->_name, $class, md5(serialize(array($path, $attributes))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                } else {
                        $cachekey = null;
                }

                // 
                // Select attribute map:
                // 
                $attrmap = $this->_attrmap[$class];

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
                if (($result = ldap_read($this->_conn->handle, $path, $filter, array_values($attrmap))) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
                }

                if (($entry = ldap_first_entry($this->_conn->handle, $result)) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
                }

                if (($data = ldap_get_attributes($this->_conn->handle, $entry)) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
                }

                if (ldap_free_result($result) == false) {
                        throw new Exception(ldap_error($this->_conn->handle), ldap_errno($this->_conn->handle));
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
                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, array('entry' => $data, 'attrmap' => $attrmap));
                } else {
                        return array('entry' => $data, 'attrmap' => $attrmap);
                }
        }

        /**
         * Get multiple attributes (Principal::ATTR_XXX) for user.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (defaults to caller).
         * @return array
         * 
         * @see getAttribute()
         */
        public function getAttributes($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }

                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-attributes-%s-%s", $this->_name, $attribute, md5($principal));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                $search = $this->search(Principal::ATTR_PN, $principal, array($attribute));

                $result = new Result(array_flip($search['attrmap']));
                $result->setName($this->_name);
                $result->insert($search['entries']);

                $output = $result->getResult();
                if ($attribute == Principal::ATTR_AFFIL) {
                        $affilation = $this->_affiliation;
                        foreach ($output as $index => $array) {
                                $output[$index][$attribute] = $affilation($array[$attribute]);
                        }
                }

                // 
                // Filter out related entries not containing the
                // requested attribute:
                // 
                if (isset($cachekey)) {
                        return $this->setCacheData(
                                $cachekey, array_filter($output, function($entry) use($attribute) {
                                        return isset($entry[$attribute]);
                                })
                        );
                } else {
                        return array_filter($output, function($entry) use($attribute) {
                                return isset($entry[$attribute]);
                        });
                }
        }

        /**
         * Get single attribute (Principal::ATTR_XXX) for user.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (defaults to caller).
         * @return string
         * 
         * @see getAttribute()
         */
        public function getAttribute($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }

                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-attribute-%s-%s", $this->_name, $attribute, md5($principal));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime)[0];
                        }
                }

                $search = $this->search(Principal::ATTR_PN, $principal, array($attribute), 'person', 1);

                $result = new Result(array_flip($search['attrmap']));
                $result->setName($this->_name);
                $result->insert($search['entries']);

                $output = $result->getResult();
                if ($attribute == Principal::ATTR_AFFIL) {
                        $affilation = $this->_affiliation;
                        foreach ($output as $index => $array) {
                                $output[$index][$attribute] = $affilation($array[$attribute]);
                        }
                }

                // 
                // Flatten array to scalar. Detect x-primary (i.e. mail) attribute:
                // 
                if (isset($output[0][$attribute][$this->_primary])) {
                        $attrib = $output[0][$attribute][$this->_primary];
                } elseif (isset($output[0][$attribute][$this->_language])) {
                        $attrib = $output[0][$attribute][$this->_language];
                } elseif (count($output[0][$attribute]) == 1) {
                        $attrib = $output[0][$attribute][0];
                } else {
                        $attrib = $output[0][$attribute];
                }

                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, $attrib);
                } else {
                        return $attrib;
                }
        }

        /**
         * Get groups for user.
         * @param string $principal The user principal name.
         * @param array $attributes The attributes to return.
         * @return array
         */
        public function getGroups($principal, $attributes = null)
        {
                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-groups-%s", $this->_name, md5(serialize(array($principal, $attributes))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                // 
                // Get distinguished names for all user principal groups:
                // 
                $member = strtolower($this->_attrmap['group'][Group::ATTR_PARENT]);
                $mapped = $this->getAttributes($member, $principal);
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
                $result = new Result(array_flip($search['attrmap']));
                $result->setName($this->_name);
                $result->append($groups);

                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, $result->getResult());
                } else {
                        return $result->getResult();
                }
        }

        /**
         * Get multiple user principal objects.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param array $options Various search options (optional).
         * 
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipals($needle, $search = null, $options = null)
        {
                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-principals-%s-%s", $this->_name, $search, md5(serialize(array($needle, $options))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                // 
                // Search for attribute matching needle:
                // 
                $search = $this->search($search, $needle, $options['attr'], 'person', $options['limit']);

                // 
                // Collect group data in result object:
                // 
                $result = new Result(array_flip($search['attrmap']));
                $result->setName($this->_name);
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
                                                $affilation = $this->_affiliation;
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
                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, $principals);
                } else {
                        return $principals;
                }
        }

        /**
         * Get single user principal object.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param string $domain The search domain (optional).
         * @param array|string $attr The attributes to return (optional).
         * 
         * @return Principal The matching user principal object.
         */
        function getPrincipal($needle, $search = null, $domain = null, $attr = null)
        {
                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-principal-%s-%s-%s", $this->_name, $search, $domain, md5(serialize(array($needle, $attr))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                if (($principals = $this->getPrincipals($needle, $search, array(
                        'domain' => $domain,
                        'attr'   => $attr,
                        'limit'  => 1
                    ))) == null) {
                        return null;
                }

                // 
                // Return user principals:
                // 
                if (isset($cachekey)) {
                        $result = $this->setCacheData($cachekey, $principals);
                } else {
                        $result = $principals;
                }

                return $result[0];
        }

        /**
         * Get members of group.
         * @param string $group The group name.
         * @param string $domain Restrict search to domain.
         * @param array $attributes The attributes to return.
         * @return Principal[]
         */
        public function getMembers($group, $domain = null, $attributes = null)
        {
                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-members-%s", $this->_name, md5(serialize(array($group, $domain, $attributes))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                // 
                // Search in group member attribute:
                // 
                $member = $this->_attrmap['group'][Group::ATTR_MEMBER];
                $search = $this->search(Group::ATTR_NAME, $group, array($member), 'group');
                $users = array();

                // 
                // Load members into result:
                // 
                $result = new Result(array_flip($search['attrmap']));
                $result->setName($this->_name);
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
                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, $principals);
                } else {
                        return $principals;
                }
        }

}
