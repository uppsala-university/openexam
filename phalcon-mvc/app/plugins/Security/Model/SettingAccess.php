<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SettingAccess.php
// Created: 2014-11-29 23:13:30
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\User;
use OpenExam\Models\Setting;

/**
 * Access control for the Setting model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SettingAccess extends ObjectAccess
{

        /**
         * Check object action.
         * 
         * @param string $action The model action.
         * @param Setting $model The model object.
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
                            // 
                            // The settings is private.
                            // 
                            if ($user->getPrincipalName() != $model->user) {
                                    throw new Exception("The user settings are private and only the owner have access", Exception::ACTION);
                            }

                            return true;
                    });
        }

}
