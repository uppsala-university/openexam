<?php

namespace OpenExam\Authentication;

/**
 * Basic HTTP (WWW-Authenticate) authenticator.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
class BasicHttpAuthenticator extends ValidatorAdapter
{

        private $realm;

        /**
         * Constructor.
         * @param CredentialValidator $validator The validator callback object.
         * @param string $realm The authentication realm.
         */
        public function __construct($validator, $realm)
        {
                parent::__construct($validator);
                $this->realm = $realm;
                $this->initialize();
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

        private function initialize()
        {
                if (isset($_SERVER['PHP_AUTH_USER'])) {
                        $this->validator->setCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                }
        }

}
