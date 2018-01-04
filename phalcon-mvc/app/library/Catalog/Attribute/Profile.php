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
// File:    Profile.php
// Created: 2016-11-13 22:33:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Models\User;

/**
 * The attribute profile interface.
 * 
 * Profiles are factory objects that given SAML attributes array as input 
 * creates user principal objects and user models. These factories provides
 * for a clean separation between generic SAML authenticators and the user
 * attributes returned from them.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Profile
{

        /**
         * Get user principal object.
         * @param array $attr The attributes array.
         * @return Principal 
         */
        function getPrincipal($attr);

        /**
         * Get user model.
         * @param array $attr The attributes array.
         * @return User
         */
        function getUser($attr);
}
