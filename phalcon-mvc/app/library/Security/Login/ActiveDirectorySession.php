<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
