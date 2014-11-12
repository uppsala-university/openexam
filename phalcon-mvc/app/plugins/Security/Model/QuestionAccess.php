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
                // Perform access control in a trusted context:
                // 
                return $this->trustedContextCall(function($role) use($action, $model, $user) {
                            // 
                            // Check role on exam, question or global:
                            // 
                            if ($role == Roles::CONTRIBUTOR ||
                                $role == Roles::CREATOR ||
                                $role == Roles::DECODER ||
                                $role == Roles::INVIGILATOR ||
                                $role == Roles::STUDENT) {
                                    if ($user->roles->aquire($role, $model->exam_id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::CORRECTOR) {
                                    if ($user->roles->aquire($role, $model->id)) {
                                            return true;
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
                    });
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
                // Perform access control in a trusted context:
                // 
                return $this->trustedContextCall(function($role) use($action, $model, $user) {
                            // 
                            // Students should not have access to questions before
                            // the exam starts.
                            // 
                            if ($role == Roles::STUDENT) {
                                    if ($model->exam->getState()->has(State::UPCOMING)) {
                                            throw new Exception('access');
                                    }
                            }

                            // 
                            // Only publisher or exam creator should have permission 
                            // to update or delete this question.
                            // 
                            if ($action == self::UPDATE || $action == self::DELETE) {
                                    if ($role != Roles::CREATOR &&
                                        $user->getPrincipalName() != $model->user) {
                                            throw new Exception('action');
                                    }
                            }

                            return true;
                    });
        }

}
