<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
