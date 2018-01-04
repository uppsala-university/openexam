<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    ActiveDirectorySession.php
// Created: 2015-03-11 13:42:06
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use Phalcon\Session\Adapter as SessionAdapter;

/**
 * Microsoft active directory login.
 * 
 * Same as ActiveDirectoryLogin, but session aware making it possible to 
 * call getSubject() not only when authenticating the request. This login
 * class also supports restoring expired system sessions. 
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ActiveDirectorySession extends ActiveDirectoryLogin
{

        public function getSubject()
        {
                $session = self::getSession();

                if ($session->has($this->name)) {
                        return $session->get($this->name)['user'];
                }
        }

        public function accepted()
        {
                $session = self::getSession();

                if ($session->status() == PHP_SESSION_DISABLED) {
                        return parent::accepted();      // fallback
                }
                if ($session->status() == PHP_SESSION_NONE) {
                        $session->start();
                }
                if ($session->has($this->name)) {
                        return true;
                }
                if (parent::accepted()) {
                        $session->set($this->name, array(
                                'user' => parent::getSubject()
                        ));
                        return true;
                } else {
                        return false;
                }
        }

        public function logout()
        {
                $session = self::getSession();

                if ($session->status() == PHP_SESSION_DISABLED) {
                        return;
                }
                if ($session->status() == PHP_SESSION_ACTIVE) {
                        $session->destroy();
                }

                parent::logout();
        }

        /**
         * Get session adapter.
         * @return SessionAdapter
         */
        private static function getSession()
        {
                return \Phalcon\DI::getDefault()->get('session');
        }

}
