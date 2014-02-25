<?php

namespace OpenExam\Authentication;

use \Phalcon\Mvc\User\Component;

/**
 * Authenticator for LDAP. The authentication is done by simple binding 
 * to the LDAP tree. The authentication token (username) is saved in the
 * session property bag (private for this class).
 * 
 * The username and password is expected to be obtained from the request
 * parameters using the class constant username and password as parameter
 * names. The credentials for authentication can be overridden by calling 
 * setCredentials().
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package OpenExam
 * @subpackage Authentication
 */
class LdapAuthenticator extends Component implements Authenticator
{

        /**
         * The request parameter containing the username.
         */
        const username = 'user';
        /**
         * The request parameter containing the password.
         */
        const password = 'pass';

        private $server;
        private $port;
        private $options;
        private $handle;        // LDAP connection
        private $user;
        private $pass;

        /**
         * Constructor.
         * @param string $server The LDAP server.
         * @param int $port Port on server.
         * @param array $options Associative array of miscellanous LDAP options.
         * @see ldap_set_options()
         */
        public function __construct($server, $port = 389, $options = array())
        {
                $this->server = $server;
                $this->port = $port;
                $this->options = $options;
        }

        /**
         * Set credentials for authentication.
         * @param string $user The username.
         * @param string $pass The password.
         */
        public function setCredentials($user, $pass)
        {
                $this->user = $user;
                $this->pass = $pass;
        }

        public function authenticated()
        {
                return $this->persistent->has(self::username);
        }

        public function getUser()
        {
                $this->persistent->get(self::username);
        }

        public function login()
        {
                if (!isset($this->user)) {
                        $this->user = $this->param(self::username);
                }
                if (!isset($this->pass)) {
                        $this->pass = $this->param(self::password);
                }
                $this->authenticate();
        }

        public function logout()
        {
                $this->persistent->remove(self::username);
        }

        private function param($name)
        {
                if (!isset($this->request->get($name))) {
                        throw new Exception(sprintf("Missing required request parameter '%s'", $name));
                } else {
                        return $this->request->get($name);
                }
        }

        private function authenticate()
        {
                $this->connect();
                $this->bind();
                $this->persistent->set(self::username, $this->user);
                $this->disconnect();
        }

        private function connect()
        {
                if (!($this->handle = ldap_connect($this->server, $this->port))) {
                        throw new Exception(sprintf("Failed connect to ''%s:%d''", $this->server, $this->port));
                }
                foreach ($this->options as $option => $value) {
                        if (!ldap_set_option($this->server, $option, $value)) {
                                ldap_close($this->handle);
                                throw new Exception("Failed set option.");
                        }
                }
        }

        private function bind()
        {
                if (!ldap_bind($this->handle, $this->user, $this->pass)) {
                        ldap_close($this->handle);
                        throw new Exception(sprintf("Failed authenticate as '%s'"), $this->user);
                } else {
                        ldap_unbind($this->handle);
                }
        }

        private function disconnect()
        {
                if (is_resource($this->handle)) {
                        ldap_close($this->handle);
                }
        }

}
