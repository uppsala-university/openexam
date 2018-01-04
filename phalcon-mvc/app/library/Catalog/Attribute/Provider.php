<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Provider.php
// Created: 2016-11-13 14:05:18
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Models\User;

/**
 * User attribute provider.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Provider
{

        /**
         * Return true if attributes are present.
         * @return boolean
         */
        function hasAttributes();

        /**
         * Get user attributes.
         * @return array 
         */
        function getAttributes();

        /**
         * Get user principal object.
         * @return Principal 
         */
        function getPrincipal();

        /**
         * Get user model.
         * @return User
         */
        function getUser();
}
