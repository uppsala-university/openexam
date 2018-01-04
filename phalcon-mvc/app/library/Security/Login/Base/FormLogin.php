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
// File:    FormLogin.php
// Created: 2014-09-10 15:33:27
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login\Base;

use Phalcon\Forms\Form;

/**
 * Interface for form based login.
 * 
 * Common interface for login classes that is processing request parameters 
 * used for authentication. How the authentication is done is undefined. It
 * could be handle local (e.g. password file or SQL) or against an LDAP
 * server.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface FormLogin
{

        /**
         * The form name or null.
         * @return string
         */
        function form();

        /**
         * The username request parameter name.
         * @return string
         */
        function user();

        /**
         * The password request parameter name.
         * @return string
         */
        function pass();
        
        /**
         * Create the login form.
         * @return Form 
         */
        function create();
}
