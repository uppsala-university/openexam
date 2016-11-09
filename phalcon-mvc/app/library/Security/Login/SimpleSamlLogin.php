<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SimpleSamlLogin.php
// Created: 2016-11-09 01:22:43
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Security\Login\Base\RemoteLogin;
use Phalcon\Config;
use UUP\Authentication\Authenticator\SimpleSamlAuthenticator;

/**
 * Simple SAML login (i.e. discovery in SWAMID).
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SimpleSamlLogin extends SimpleSamlAuthenticator implements RemoteLogin
{

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param string $name The SP name.
         */
        public function __construct($config, $name = 'default-sp', $path = null)
        {
                parent::__construct(array(
                        'name' => $name,
                        'path' => $path
                ));
                parent::control(self::SUFFICIENT);
                parent::visible(true);

                $this->target = $config->application->baseUri . "auth/login";
                $this->return = $config->application->baseUri . "auth/logout";
        }

        /**
         * Current server name.
         * @return string
         */
        public function hostname()
        {
                return filter_input(INPUT_SERVER, 'SERVER_NAME');
        }

        /**
         * Current server port.
         * @return string
         */
        public function port()
        {
                return filter_input(INPUT_SERVER, 'SERVER_PORT');
        }

        /**
         * Get server path.
         * @return string
         */
        public function path()
        {
                return null;    // Use default login path
        }

}
