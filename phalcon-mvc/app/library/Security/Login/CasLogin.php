<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CasLogin.php
// Created: 2014-09-10 15:38:26
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Security\Login\Base\RemoteLogin;
use UUP\Authentication\Authenticator\CasAuthenticator;

/**
 * CAS Login.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CasLogin extends CasAuthenticator implements RemoteLogin
{

        /**
         * The user domain.
         */
        public $domain;

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param string $host The CAS server name (FQHN).
         * @param int $port The CAS server port.
         * @param string $path The CAS service path (relative to host).
         */
        public function __construct($config, $host, $port = 443, $path = '/cas')
        {
                parent::__construct($host, $port, $path);
                parent::control(self::SUFFICIENT);
                parent::visible(true);

                $this->return = $config->application->baseUri . "auth/logout";
                $this->domain = $config->user->domain;
        }

        /**
         * Get CAS server name or IP-address.
         * @return string
         */
        public function hostname()
        {
                return $this->host;
        }

        /**
         * Get CAS sever port.
         * @return string
         */
        public function port()
        {
                return $this->port;
        }

        /**
         * Get CAS server path.
         * @return string
         */
        public function path()
        {
                return $this->path;
        }

        /**
         * Get user domain.
         * @return string
         */
        public function getDomain()
        {
                return $this->domain;
        }

        /**
         * Set user domain.
         * @param string $domain The user domain.
         */
        public function setDomain($domain)
        {
                $this->domain = $domain;
        }

        /**
         * Get user principal name.
         * @return string
         */
        public function getSubject()
        {
                return sprintf("%s@%s", parent::getSubject(), $this->domain);
        }

}
