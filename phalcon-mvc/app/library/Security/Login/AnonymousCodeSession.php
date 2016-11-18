<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AnonymousCodeSession.php
// Created: 2016-11-18 08:06:44
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Database\SessionAdapter;

/**
 * Anonymous code login with session handling.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AnonymousCodeSession extends AnonymousCodeLogin
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
