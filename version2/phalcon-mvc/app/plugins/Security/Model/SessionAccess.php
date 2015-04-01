<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SessionAccess.php
// Created: 2015-04-01 13:14:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\User;
use OpenExam\Models\Session;

/**
 * Access control for the session model.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SessionAccess extends ObjectAccess
{

        /**
         * Check object action.
         * 
         * @param string $action The model action.
         * @param Session $model The model object.
         * @param User $user The peer object.
         * @return boolean
         */
        public function checkObjectAction($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getResourceName(), $user->getPrincipalName()
                        ));
                }
                
                // 
                // Perform access control in a trusted context:
                // 
                return $this->trustedContextCall(function($role) use($action, $model, $user) {
                            if (strpos($model->data, $user->getUser()) == false) {
                                    throw new Exception("The session is private and only the owner have access", Exception::ACTION);
                            }
                            return true;
                    });
        }

}
