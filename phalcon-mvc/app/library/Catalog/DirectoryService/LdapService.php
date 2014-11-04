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

use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Catalog\ServiceAdapter;

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
         * LDAP connection handle.
         * @var resource 
         */
        private $ldap;
        /**
         * LDAP_OPT_XXX options.
         * @var array 
         */
        private $options;
        /**
         * Attribute map.
         * @var array 
         */
        private $attrmap = array(
                Principal::ATTR_UID  => 'uid',
                Principal::ATTR_SN   => 'sn',
                Principal::ATTR_CN   => 'cn',
                Principal::ATTR_GN   => 'givenName',
                Principal::ATTR_MAIL => 'mail',
                Principal::ATTR_PNR  => 'norEduPersonNIN',
                Principal::ATTR_PN   => 'eduPersonPrincipalName',
                Principal::ATTR_ALL  => '*'
        );
        /**
         * The search base DN.
         * @var string 
         */
        private $basedn;

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
                $this->host = $host;
                $this->port = $port;
                $this->user = $user;
                $this->pass = $pass;
                $this->options = $options;
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
         * Set attribute map.
         * 
         * The $attrmap argument is merged with the existing attribute map.
         * @param array $attrmap The attribute map.
         */
        public function setAttributeMap($attrmap)
        {
                $this->attrmap = array_merge($this->attrmap, $attrmap);
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
         * Set the search base DN (e.g. DC=example,DC=com).
         * @param string $basedn
         */
        public function setBase($basedn)
        {
                $this->basedn = $basedn;
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

                if (ldap_bind($this->ldap, $this->user, $this->pass) == false) {
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
                $this->connect();

                // 
                // Prepare attribute map:
                // 
                $attrmap = $this->attrmap;
                foreach ($attrmap as $key => $val) {
                        $attrmap[$key] = strtolower($val);
                }
                $attrmap[$attribute] = $attribute;        // Add identity map

                $filter = sprintf("(%s=%s)", $attrmap[Principal::ATTR_PN], $principal);

                if (($result = ldap_search($this->ldap, $this->basedn, $filter, array($attrmap[$attribute]), 0)) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                if (($entries = ldap_get_entries($this->ldap, $result)) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                if (ldap_free_result($result) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                $result = new LdapResult($attribute, array_flip($attrmap));
                for ($i = 0; $i < $entries['count']; $i++) {
                        $result->insert($entries[$i]);
                }

                $data = $result->getResult();
                return $data[$attribute];
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
         * @param string $search The attribute to query.
         * @param array $options Various search options.
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipal($needle, $search, $options)
        {
                $this->connect();

                // 
                // Prepare attribute map:
                // 
                $attrmap = $this->attrmap;
                foreach ($attrmap as $key => $val) {
                        $attrmap[$key] = strtolower($val);
                }

                // 
                // Add identity map:
                // 
                if (!isset($attrmap[$search])) {
                        $attrmap[$search] = $search;
                }

                // 
                // Prepare search options:
                // 
                $filter = sprintf("(%s=%s)", $attrmap[$search], $needle);

                // 
                // Substitute generic principal object attributes, while 
                // preserving service specific attributes:
                // 
                for ($i = 0; $i < count($options['attr']); $i++) {
                        if (isset($attrmap[$options['attr'][$i]])) {
                                $options['attr'][$i] = $attrmap[$options['attr'][$i]];
                        } else {
                                $attrmap[$options['attr'][$i]] = $options['attr'][$i];
                        }
                }

                // 
                // Perform LDAP search:
                // 
                if (($result = ldap_search($this->ldap, $this->basedn, $filter, $options['attr'], 0, $options['limit'])) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                if (($entries = ldap_get_entries($this->ldap, $result)) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                if (ldap_free_result($result) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                // 
                // Create principal objects array from directory entries.
                // 
                $principals = array();
                $revattrmap = array_flip($attrmap);
                $result = new LdapResult('*', $revattrmap);

                for ($i = 0; $i < $entries['count']; $i++) {
                        $result->replace($entries[$i]);
                        $data = $result->getResult();

                        // 
                        // Create user principal objects from search result:
                        // 
                        $principal = new Principal();
                        foreach ($data['*'] as $name => $attrs) {
                                $attr = $revattrmap[$name];
                                if (property_exists($principal, $attr)) {
                                        if ($attr == Principal::ATTR_MAIL) {
                                                $principal->mail = $attrs;
                                                unset($data['*'][$name]);
                                        } else {
                                                $principal->$attr = $attrs[0];
                                                unset($data['*'][$name]);
                                        }
                                }
                        }

                        // 
                        // Any left over attributes goes in attr member:
                        // 
                        if ($options['data'] && count($data['*']) != 0) {
                                $principal->attr = $data['*'];
                        }

                        $principals[] = $principal;
                }

                return $principals;
        }

        /**
         * Opens connection on-demand.
         */
        protected function connect()
        {
                if (!$this->connected()) {
                        $this->open();
                }
        }

}

/**
 * The LDAP search result.
 */
class LdapResult
{

        /**
         * The attributes filter.
         * @var string 
         */
        private $attribute;
        /**
         * Reverse attribute map.
         * @var array 
         */
        private $revattr;
        /**
         * The result array.
         * @var array 
         */
        private $result = array();

        /**
         * Constructor.
         * @param string $attribute
         */
        public function __construct($attribute, $revattr)
        {
                $this->attribute = $attribute;
                $this->revattr = $revattr;
        }

        /**
         * Insert entry in result.
         * @param array $entry The directory entry.
         */
        public function insert($entry)
        {
                for ($i = 0; $i < $entry['count']; $i++) {
                        $key = $entry[$i];
                        $val = $entry[$key];
                        $this->add($key, $val, $this->attribute);
                }
        }

        /**
         * Append entry using new attribute key.
         * @param array $entry The directory entry.
         * @param string $attribute The attribute name.
         */
        public function append($entry, $attribute)
        {
                $this->attribute = $attribute;
                $this->insert($entry);
        }

        /**
         * Replace result with new entry.
         * @param array $entry The directory entry.
         */
        public function replace($entry)
        {
                $this->result = array();
                $this->insert($entry);
        }

        /**
         * Add entry to result.
         * @param string $key The entry key.
         * @param array $val The entry data.
         */
        private function add($key, $val, $attribute)
        {
                list($key, $sub) = explode(';', $key);
                if ($attribute == Principal::ATTR_ALL || strcasecmp($this->revattr[$key], $attribute) == 0) {
                        if (!isset($this->result[$attribute])) {
                                $this->result[$attribute] = array();
                        }
                        if (!isset($this->result[$attribute][$key])) {
                                $this->result[$attribute][$key] = array();
                        }
                        for ($i = 0; $i < $val['count']; $i++) {
                                if (!in_array($val[$i], $this->result[$attribute][$key])) {
                                        $this->result[$attribute][$key][] = $val[$i];
                                }
                                if (isset($sub)) {
                                        $this->result[$attribute][$key][$sub] = $val[0];
                                }
                        }
                }
        }

        /**
         * Get result array.
         * @return array
         */
        public function getResult()
        {
                return $this->result;
        }

}
