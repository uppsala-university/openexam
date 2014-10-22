<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DirectoryServiceAdapter.php
// Created: 2014-10-22 04:17:43
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use Phalcon\Mvc\User\Component;

/**
 * Directory service adapter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class DirectoryServiceAdapter extends Component implements DirectoryService
{

        public function getEmail($principal)
        {
                return array();
        }

        public function getGroups($principal)
        {
                return array();
        }

        public function getMembers($group, $domain = null)
        {
                return array();
        }

        /**
         * Open connection to backend server.
         * @return bool True if successful connected.
         */
        abstract function open();

        /**
         * Close connection to backend server.
         */
        abstract function close();
        
        /**
         * Check if connected to backend server.
         * @return bool True if already connected.
         */
        abstract function connected();
}
