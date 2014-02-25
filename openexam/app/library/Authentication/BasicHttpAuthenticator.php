<?php

namespace OpenExam\Authentication;

/**
 * Basic HTTP (WWW-Authenticate) authenticator.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
class BasicHttpAuthenticator implements Authenticator
{

        private $validator;
        private $realm;

        /**
         * Constructor.
         * @param CredentialValidator $validator The validator callback object.
         * @param string $realm The authentication realm.
         */
        public function __construct($validator, $realm)
        {
                $this->validator = $validator;
                $this->realm = $realm;
                $this->initialize();
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
                if (!isset($_SERVER['PHP_AUTH_USER'])) {
                        header(sprintf('WWW-Authenticate: Basic realm="%s"', $this->realm));
                        header('HTTP/1.0 401 Unauthorized');
                        exit;
                } else {
                        $this->validator->login();
                }
        }

        public function logout()
        {
                $this->validator->logout();
        }

        private function initialize()
        {
                if (isset($_SERVER['PHP_AUTH_USER'])) {
                        $this->validator->setCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                }
        }

}
