<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    TeacherAccess.php
// Created: 2014-09-30 14:55:05
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\User;
use OpenExam\Models\Teacher;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * Access control for the Teacher model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TeacherAccess extends ObjectAccess
{

        /**
         * Check model access.
         * @param string $action The model action.
         * @param Teacher $model The model.
         * @param User $user The peer object.
         */
        public function checkAccess($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getResourceName(), $user->getPrincipalName()
                        ));
                }

                return true;
        }

}
