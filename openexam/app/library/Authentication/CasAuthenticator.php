<?php

namespace OpenExam\Authentication;

require_once 'CAS.php';

/**
 * Authenticator for CAS.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
class CasAuthenticator implements Authenticator
{

        private $host;
        private $port;
        private $path;

        public function __construct($host, $port = 443, $path = "/cas")
        {
                $this->host = $host;
                $this->port = $port;
                $this->path = $path;
                $this->initialize();
        }

        public function authenticated()
        {
                return strlen(phpCAS::getUser() != 0);
        }

        public function getUser()
        {
                return phpCAS::getUser();
        }

        public function login()
        {
                phpCAS::forceAuthentication();
        }

        public function logout()
        {
                phpCAS::logout();
        }

        private function initialize()
        {
                phpCAS::client(CAS_VERSION_2_0, $this->host, $this->port, $this->path);
                phpCAS::setNoCasServerValidation();
        }

}
