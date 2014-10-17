<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    QuestionAccess.php
// Created: 2014-09-30 14:52:17
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Models\Question;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * Access control for the Question model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class QuestionAccess extends ObjectAccess
{

        /**
         * Check object role.
         * 
         * @param string $action The model action.
         * @param Question $model The model object.
         * @param User $user The peer object.
         * @return boolean
         */
        public function checkObjectRole($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getResourceName(), $user->getPrincipalName()
                        ));
                }

                // 
                // Temporarily disable access control:
                // 
                $role = $user->setPrimaryRole(Roles::TRUSTED);

                // 
                // Check role on exam, question or global:
                // 
                if ($role == Roles::CONTRIBUTOR ||
                    $role == Roles::CREATOR ||
                    $role == Roles::DECODER ||
                    $role == Roles::INVIGILATOR ||
                    $role == Roles::STUDENT) {
                        if ($user->roles->aquire($role, $model->exam_id)) {
                                $user->setPrimaryRole($role);
                                return true;
                        }
                } elseif ($role == Roles::CORRECTOR) {
                        if ($user->roles->aquire($role, $model->id)) {
                                $user->setPrimaryRole($role);
                                return true;
                        }
                } elseif (isset($role)) {
                        if ($user->roles->aquire($role)) {
                                $user->setPrimaryRole($role);
                                return true;
                        }
                }

                if (isset($role)) {
                        $user->setPrimaryRole($role);
                        throw new Exception('role');
                } else {
                        $user->setPrimaryRole($role);
                        return true;
                }
        }

        /**
         * Check object action.
         * 
         * @param string $action The model action.
         * @param Question $model The model object.
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
                // Temporarily disable access control:
                // 
                $role = $user->setPrimaryRole(Roles::TRUSTED);

                // 
                // Students should not have access to questions before
                // the exam starts.
                // 
                if ($role == Roles::STUDENT) {
                        if($model->exam->getState()->has(State::UPCOMING)) {
                                $user->setPrimaryRole($role);
                                throw new Exception('access');
                        }
                }
        }

}
