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
                if (isset($_SESSION[$this->name])) {
                        return $_SESSION[$this->name]['user'];
                }
        }

        public function accepted()
        {
                if (session_status() == PHP_SESSION_DISABLED) {
                        return parent::accepted();      // fallback
                }
                if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                }
                if (isset($_SESSION[$this->name])) {
                        return true;
                }
                if (parent::accepted()) {
                        $_SESSION[$this->name] = array('user' => parent::getSubject());
                        return true;
                } else {
                        return false;
                }
        }

        public function logout()
        {
                if (session_status() == PHP_SESSION_DISABLED) {
                        return;
                }

                unset($_SESSION[$this->name]);
                parent::logout();
        }

}
