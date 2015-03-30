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
         * Check object role.
         * 
         * @param string $action The model action.
         * @param Answer $model The model object.
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
                            // Check role on exam or question:
                            // 
                            if ($role == Roles::CONTRIBUTOR ||
                                $role == Roles::CREATOR ||
                                $role == Roles::DECODER ||
                                $role == Roles::INVIGILATOR) {
                                    if ($user->roles->aquire($role, $model->question->exam_id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::STUDENT) {
                                    if ($user->roles->aquire($role, $model->student->exam_id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::CORRECTOR) {
                                    if ($user->roles->aquire($role, $model->question->id)) {
                                            return true;
                                    }
                            } elseif (isset($role)) {
                                    if ($user->roles->aquire($role)) {
                                            return true;
                                    }
                            }

                            if (isset($role)) {
                                    throw new Exception(sprintf("Failed aquire role %s", $role), Exception::ROLE);
                            } else {
                                    return true;
                            }
                    });
        }

        /**
         * Check object action.
         * 
         * @param string $action The model action.
         * @param Answer $model The model object.
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
                            // Students can only access their own answers:
                            // 
                            if ($role == Roles::STUDENT) {
                                    if ($action != self::CREATE) {
                                            if ($model->student->user != $user->getPrincipalName()) {
                                                    throw new Exception("Only the owner can access this object", Exception::OWNER);
                                            }
                                    }
                            }

                            // 
                            // Don't allow real answers to be deleted.
                            // 
                            if ($action == self::DELETE && $model->answered) {
                                    throw new Exception("Deleting answer is not allowed", Exception::ACTION);
                            }

                            return true;
                    });
        }

}
