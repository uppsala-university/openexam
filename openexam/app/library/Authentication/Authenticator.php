<?php

namespace OpenExam\Authentication;

/**
 * The interface for all authenticator classes.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
interface Authenticator
{

        /**
         * Perform login for this authenticator.
         */
        function login();

        /**
         * Perform logout for this authenticator.
         */
        function logout();

        /**
         * Check if caller is already authenticated.
         * @return bool
         */
        function authenticated();

        /**
         * Get logged on username.
         * @return string
         */
        function getUser();
}
