<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenExam\Authentication;

use OpenExam\Authentication\Validator\CredentialValidator;

/**
 * Adapter class between the authenticator frontend and the validator backend.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
class ValidatorAdapter implements Authenticator
{

        /**
         * @var CredentialValidator 
         */
        protected $validator;

        /**
         * Constructor.
         * @param CredentialValidator $validator The credentials validator backend.
         */
        public function __construct($validator)
        {
                $this->validator = $validator;
        }

        public function authenticated()
        {
                return $this->validator->authenticated();
        }

        public function getUser()
        {
                return $this->validator->getUser();
        }

        public function login()
        {
                $this->validator->login();
        }

        public function logout()
        {
                $this->validator->logout();
        }

}
