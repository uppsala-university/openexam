<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Profile.php
// Created: 2016-11-13 22:33:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute;

/**
 * The atribute profile interface.
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
