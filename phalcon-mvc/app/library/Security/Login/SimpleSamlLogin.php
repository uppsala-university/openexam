<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    SimpleSamlLogin.php
// Created: 2016-11-09 01:22:43
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Catalog\Attribute\Profile;
use OpenExam\Library\Catalog\Attribute\Profile\Swamid as SwamidProfile;
use OpenExam\Library\Catalog\Attribute\Provider as AttributeProvider;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Security\Login\Base\RemoteLogin;
use OpenExam\Models\User;
use Phalcon\Config;
use UUP\Authentication\Authenticator\SimpleSamlAuthenticator;

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
                        'spid' => $name,
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
         * Handle logout action.
         */
        public function logout()
        {
                // 
                // TODO: Fix SSO-logout from SAML service.
                // 
                /*
                 * Right now I'm getting error from IdP about missing cookies on logout. The 
                 * same happens when using Simple SAML builtin test console. 
                 * 
                 * For now on, just rely on session cleanup but keep SSO functionality.
                 */
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
