<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ExamAccess.php
// Created: 2014-09-30 14:50:33
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Models\Exam;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * Access control for the Exam model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ExamAccess extends ObjectAccess
{

        /**
         * Check model access.
         * @param string $action The model action.
         * @param Exam $model The model.
         * @param User $user The peer object.
         */
        public function checkAccess($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getName(), $user->getPrincipalName()
                        ));
                }

                $role = $user->getPrimaryRole();

                // 
                // Check role on exam, question or global:
                // 
                if ($role == Roles::CONTRIBUTOR ||
                    $role == Roles::CREATOR ||
                    $role == Roles::DECODER ||
                    $role == Roles::INVIGILATOR ||
                    $role == Roles::STUDENT) {
                        if ($user->roles->aquire($role, $model->id)) {
                                return true;
                        }
                } elseif ($role == Roles::CORRECTOR) {
                        foreach ($model->questions as $question) {
                                if ($user->roles->aquire($role, $question->id)) {
                                        return true;
                                }
                        }
                } elseif (isset($role)) {
                        if ($user->roles->aquire($role)) {
                                return true;
                        }
                }

                if (isset($role)) {
                        throw new Exception('role');
                } else {
                        return true;
                }
        }

}
