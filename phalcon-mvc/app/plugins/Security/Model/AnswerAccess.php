<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AnswerAccess.php
// Created: 2014-09-30 14:47:07
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Models\Answer;

/**
 * Access control for the Answer model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AnswerAccess extends ObjectAccess
{

        /**
         * Behaviour hook.
         * @param string $event
         * @param Answer $model
         * @param User $user The peer object.
         * @return boolean 
         * @throws Exception
         */
        public function notify($event, $model, $user)
        {
                printf("%s: event=%s, model=%s, user=%s\n", __METHOD__, $event, $model->getName(), $user->getPrincipalName());

                $role = $user->getPrimaryRole();

                // 
                // Object access control:
                // 
                if ($model->id != 0) {
                        if ($role == Roles::STUDENT) {
                                if ($model->Student->user != $user->getPrincipalName()) {
                                        throw new Exception('owner');
                                }
                        }
                }

                // 
                // Check role on exam or question:
                // 
                if ($role == Roles::CONTRIBUTOR ||
                    $role == Roles::CREATOR ||
                    $role == Roles::DECODER ||
                    $role == Roles::INVIGILATOR) {
                        if ($user->roles->aquire($role, $model->question->exam_id) == false) {
                                throw new Exception('role');
                        }
                } elseif ($role == Roles::STUDENT) {
                        if ($user->roles->aquire($role, $model->student->exam_id) == false) {
                                throw new Exception('role');
                        }
                } elseif ($role == Roles::CORRECTOR) {
                        if ($user->roles->aquire($role, $model->question->id) == false) {
                                throw new Exception('role');
                        }
                } elseif (isset($role)) {
                        if ($user->roles->aquire($role) == false) {
                                throw new Exception('role');
                        }
                }

                return true;
        }

}
