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

use OpenExam\Library\Catalog\Attribute\Profile;
use OpenExam\Library\Catalog\Attribute\Provider as AttributeProvider;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Security\Login\Base\RemoteLogin;
use OpenExam\Models\User;
use Phalcon\Config;
use UUP\Authentication\Authenticator\SimpleSamlAuthenticator;
use OpenExam\Library\Catalog\Attribute\Profile\Swamid as SwamidProfile;

/**
 * Simple SAML login (i.e. discovery in SWAMID).
 * 
 * Provides attributes to user principal object and user model thru selected
 * profile (the attribute mapper). The default profile is SWAMID.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SimpleSamlLogin extends SimpleSamlAuthenticator implements RemoteLogin, AttributeProvider
{

        /**
         * The attribute profile.
         * @var Profile
         */
        private $_profile;

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

                $this->_profile = new SwamidProfile();
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

        /**
         * Set attribute profile.
         * @param Profile $profile The attribute profile.
         */
        public function setProfile($profile)
        {
                $this->_profile = $profile;
        }

        /**
         * Return true if attributes are present.
         * @return boolean
         */
        public function hasAttributes()
        {
                return count(parent::attributes()) != 0;
        }

        /**
         * Get attributes array.
         * @return array
         */
        public function getAttributes()
        {
                return parent::attributes();
        }

        /**
         * Get user principal object.
         * @return Principal
         */
        public function getPrincipal()
        {
                return $this->_profile->getPrincipal(parent::attributes());
        }

        /**
         * Get user model.
         * @return User
         */
        public function getUser()
        {
                return $this->_profile->getUser(parent::attributes());
        }

}
