<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LdapConnection.php
// Created: 2014-10-22 04:21:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService\Ldap;

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
        private $_ldap;
        /**
         * The LDAP server hostname.
         * @var string 
         */
        private $_host;
        /**
         * The LDAP server port.
         * @var int 
         */
        private $_port;
        /**
         * The LDAP bind username.
         * @var string 
         */
        private $_user;
        /**
         * The LDAP bind password.
         * @var string 
         */
        private $_pass;
        /**
         * LDAP_OPT_XXX options.
         * @var array 
         */
        private $_options;

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
                $this->_host = $host;
                $this->_port = $port;
                $this->_user = $user;
                $this->_pass = $pass;
                $this->_options = $options;
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
                return $this->_ldap;
        }

        /**
         * Set LDAP connection option.
         * @param string $name The LDAP_OPT_XXX constant.
         * @param mixed $value The option value.
         */
        public function setOption($name, $value)
        {
                $this->_options[$name] = $value;
        }

        /**
         * Open connection to LDAP server.
         */
        public function open()
        {
                if (($this->_ldap = ldap_connect($this->_host, $this->_port)) == false) {
                        throw new Exception(sprintf(
                            "Failed connect to LDAP server %s:%d", $this->_host, $this->_port
                        ));
                }

                foreach ($this->_options as $name => $value) {
                        if (ldap_set_option($this->_ldap, $name, $value) == false) {
                                throw new Exception(ldap_error($this->_ldap), ldap_errno($this->_ldap));
                        }
                }

                if (@ldap_bind($this->_ldap, $this->_user, $this->_pass) == false) {
                        throw new Exception(ldap_error($this->_ldap), ldap_errno($this->_ldap));
                }
                
                return true;
        }

        /**
         * Close connection to LDAP server.
         */
        public function close()
        {
                if (ldap_unbind($this->_ldap) == false) {
                        throw new Exception(ldap_error($this->_ldap), ldap_errno($this->_ldap));
                }
        }

        /**
         * Check if connected to LDAP server.
         * @return bool
         */
        public function connected()
        {
                return is_resource($this->_ldap);
        }

        /**
         * Get connection hostname.
         * @return string
         */
        public function hostname()
        {
                return $this->_host;
        }

        /**
         * Get connection port.
         * @return int
         */
        public function port()
        {
                return $this->_port;
        }

}
