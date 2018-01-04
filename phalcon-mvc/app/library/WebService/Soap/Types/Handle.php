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
// File:    Handle.php
// Created: 2014-10-16 04:50:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Soap\Types;

/**
 * SOAP call handle class.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Handle
{

        /**
         * Requested role for call.
         * @var string 
         */
        public $role;
        /**
         * Username for authentication.
         * @var string 
         */
        public $user;
        /**
         * Password for authentication.
         * @var string 
         */
        public $pass;
        /**
         * Use session authentication.
         * @var boolean
         */
        public $session;

        public function __construct($role, $user = null, $pass = null)
        {
                $this->role = $role;
                $this->user = $user;
                $this->pass = $pass;
        }

}
