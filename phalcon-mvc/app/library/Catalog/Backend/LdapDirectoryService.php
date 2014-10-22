<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LdapDirectoryService.php
// Created: 2014-10-22 04:21:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Backend;

use OpenExam\Library\Catalog\DirectoryServiceAdapter;

/**
 * LDAP directory service.
 * 
 * This class provides directory service using LDAP as backend.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class LdapDirectoryService extends DirectoryServiceAdapter
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
         * Constructor.
         * @param string $host The LDAP server hostname.
         * @param string $port The LDAP server port.
         * @param string $user The LDAP bind username.
         * @param string $pass The LDAP bind password.
         */
        public function __construct($host, $port = 636, $user = null, $pass = null)
        {
                $this->host = $host;
                $this->port = $port;
                $this->user = $user;
                $this->pass = $pass;
        }

        /**
         * Open connection to LDAP server.
         */
        public function open()
        {
                if (($this->ldap = ldap_connect($this->host, $this->port)) == false) {
                        $error = sprintf(
                            "Failed connect to LDAP server %s:%d", $this->host, $this->port
                        );
                        $this->logger->system->error(__METHOD__ . ': ' . $error);
                        return false;
                }
                if (ldap_bind($this->ldap, $this->user, $this->pass) == false) {
                        $error = sprintf(
                            "Failed bind to LDAP server %s:%d as %s: %s (error=%d)", $this->host, $this->port, $this->user, ldap_error($this->ldap), ldap_errno($this->ldap)
                        );
                        $this->logger->system->error(__METHOD__ . ': ' . $error);
                }
        }

        /**
         * Close connection to LDAP server.
         */
        public function close()
        {
                ldap_close($this->ldap);
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
