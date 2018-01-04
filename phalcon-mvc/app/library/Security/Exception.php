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
// File:    Exception.php
// Created: 2014-09-02 23:15:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Security;

class Exception extends \Exception
{

        /**
         * Failed acquire role.
         */
        const ROLE = 1;
        /**
         * Caller are not the owner of requested object.
         */
        const OWNER = 2;
        /**
         * Access is denied.
         */
        const ACCESS = 3;
        /**
         * Action is not allowed.
         */
        const ACTION = 4;
        /**
         * The ACL service is missing.
         */
        const ACL = 5;
        /**
         * The user service is missing.
         */
        const USER = 6;
        /**
         * Caller is not authenticated.
         */
        const AUTH = 7;

}
