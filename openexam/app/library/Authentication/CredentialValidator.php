<?php

namespace OpenExam\Authentication;

/**
 * Credential validator callback class. This class is intended to be used 
 * as the base class for backend authenticator interfacing against external 
 * account sources (i.e. PAM or SQL).
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
abstract class CredentialValidator implements Authenticator
{

        protected $user;
        protected $pass;

        /**
         * Constructor.
         * @param string $user The username to validate.
         * @param string $pass The password to validate.
         */
        public function __construct($user = "", $pass = "")
        {
                $this->user = $user;
                $this->pass = $pass;
        }

        public function setCredentials($user, $pass)
        {
                $this->user = $user;
                $this->pass = $pass;
        }

}
