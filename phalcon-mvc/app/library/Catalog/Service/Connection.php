<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Connection.php
// Created: 2014-11-05 20:18:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service;

/**
 * The service backend connection.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Connection
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
