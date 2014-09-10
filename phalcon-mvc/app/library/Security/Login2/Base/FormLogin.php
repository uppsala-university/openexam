<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Formlogin.php
// Created: 2014-09-09 11:10:59
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Security\Login\Base;

use UUP\Authentication\Library\Authenticator\AuthenticatorBase;

/**
 * Formlogin abstract class
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
abstract class FormLogin extends LoginBase
{

        /**
         * Constructor.
         * @param AuthenticatorBase $auhenticator The wrapped authenticator.
         */
        public function __construct($authenticator)
        {
                parent::__construct(parent::form, $authenticator);
        }

}
