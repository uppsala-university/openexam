<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SystemLogin.php
// Created: 2015-03-17 10:45:08
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use Phalcon\DI;
use Phalcon\Http\RequestInterface;
use UUP\Authentication\Authenticator\HostnameAuthenticator;

/**
 * System service login.
 * 
 * Authentication handler for system local services (e.g. result rendering)
 * accepting login from the configured host and peer knowing the security
 * token. This login should only be used for connections from localhost or
 * trusted hosts.
 * 
 * By default the authenticated user becomes the accepted hostname, but can
 * be overridden by passing a username as request parameter.
 * 
 * Example: ?token=<secret key>[&user=<username>]
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SystemLogin extends HostnameAuthenticator
{

        /**
         * The secret token.
         * @var string 
         */
        private $_token;
        /**
         * The HTTP request.
         * @var RequestInterface 
         */
        private $_request;
        /**
         * The authentication subject (hostname or user).
         * @var string 
         */
        private $_subject;

        /**
         * Constructor.
         * @param string $token The security token, either absolute file path or string.
         * @param string $accept The accepted remote host.
         */
        public function __construct($token, $accept = self::LOCALHOST)
        {
                parent::__construct($accept);
                parent::control(self::SUFFICIENT);
                
                if (file_exists($token)) {
                        $this->_token = trim(file_get_contents($token));
                } elseif ($token[0] != '/') {
                        $this->_token = $token;
                } else {
                        $this->_token = 'T' . md5(rand(1, 1000) . time());
                        file_put_contents($token, $this->_token);
                        chmod($token, 400);
                }

                $this->_request = DI::getDefault()->get('request');
        }

        public function getSubject()
        {
                return $this->_subject;
        }

        public function accepted()
        {
                // 
                // Restrict login to accepted host and peer knowing the
                // security token.
                // 
                if (!parent::accepted()) {
                        return false;
                } elseif (($token = $this->_request->get('token', 'string')) == false) {
                        return false;
                } elseif ($token != $this->_token) {
                        return false;
                }

                // 
                // Accept custom subject if requested, otherwise set subject
                // to accepted remote host.
                // 
                if (($user = $this->_request->get('user', 'string'))) {
                        $this->_subject = $user;
                } else {
                        $this->_subject = parent::getSubject();
                }

                return true;
        }

}
