<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceConnection.php
// Created: 2014-11-05 20:18:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

/**
 * The service backend connection.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface ServiceConnection
{

        /**
         * Open connection to backend server.
         * @return bool True if successful connected.
         */
        public function open();

        /**
         * Close connection to backend server.
         */
        public function close();

        /**
         * Check if connected to backend server.
         * @return bool True if already connected.
         */
        public function connected();

        /**
         * Get service hostname.
         * @return string 
         */
        public function hostname();

        /**
         * Get service port.
         * @return int
         */
        public function port();
}
