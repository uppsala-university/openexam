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

namespace OpenExam\Library\Catalog\Backend;

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
                Principal::ATTR_UID => 'uid',
                Principal::ATTR_SN  => 'sn',
                Principal::ATTR_CN  => 'cn',
                Principal::ATTR_GN  => 'givenname',
                Principal::ATTR_PNR => 'norEduPersonNIN'
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
                $filter = sprintf("(%s=%s)", $this->attrmap[Principal::ATTR_UID], $principal);

                if (($result = ldap_search($this->ldap, $this->basedn, $filter, array($this->attrmap[$attribute]), 1)) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                if (($entries = ldap_get_entries($this->ldap, $result)) == false) {
                        throw new LdapException(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                return $entries;
        }

        /**
         * Get user principal object.
         * 
         * <code>
         * // Search three first Tomas in example.com domain:
         * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
         * 
         * // Get email for tomas@example.com:
         * $manager->getPrincipal('thomas@example.com', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
         * </code>
         * 
         * The $options parameter is an array containing zero or more of 
         * these fields:
         * 
         * <code>
         * array(
         *       'attr'   => array(),
         *       'limit'  => 0,
         *       'domain' => null
         * )
         * </code>
         * 
         * The attr field defines which attributes to return. The limit field 
         * limits the number of returned user principal objects (use 0 for 
         * unlimited). The query can be restricted to a single domain by 
         * setting the domain field.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query.
         * @param array $options Various search options.
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipal($needle, $search, $options)
        {
                // 
                // Prepare search options:
                // 
                $filter = sprintf("(%s=%s)", $this->attrmap[$search], $needle);

                $attributes = array();
                if (isset($options['attr'])) {
                        foreach ($options['attr'] as $attr) {
                                $attributes[$attr] = $this->attrmap[$attr];
                        }
                }
                if (count($attributes) == 0) {
                        $attributes = null;
                }

                // 
                // Perform LDAP search:
                // 
                if (($result = ldap_search($this->ldap, $this->basedn, $filter, $attributes, 0, $options['limit'])) == false) {
                        throw new Exception(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                if (($entries = ldap_get_entries($this->ldap, $result)) == false) {
                        throw new LdapException(ldap_error($this->ldap), ldap_errno($this->ldap));
                }

                // 
                // Create user principal objects from search result:
                // 
                $principals = array();
                foreach ($entries as $entry) {
                        $principal = new Principal();
                        foreach ($attributes as $attr => $mapped) {
                                $principal->$attr = $entry[$mapped];
                        }
                        $principals[] = $principal;
                }

                return $principals;
        }

}
