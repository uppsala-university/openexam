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

use OpenExam\Library\Core\Exam\State;
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
         * Check object access.
         * 
         * @param string $action The model action.
         * @param Exam $model The model object.
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
                            if ($this->logger->debug) {
                                    $this->logger->debug->log(sprintf(
                                            "%s(role=%s)", __METHOD__, $role
                                    ));
                            }

                            // 
                            // Check role on exam, question or global:
                            // 
                            if ($role == Roles::CONTRIBUTOR ||
                                $role == Roles::CREATOR ||
                                $role == Roles::DECODER ||
                                $role == Roles::INVIGILATOR ||
                                $role == Roles::STUDENT) {
                                    if ($user->roles->acquire($role, $model->id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::CORRECTOR) {
                                    foreach ($model->questions as $question) {
                                            if ($user->roles->acquire($role, $question->id)) {
                                                    return true;
                                            }
                                    }
                            } elseif (isset($role)) {
                                    if ($user->roles->acquire($role)) {
                                            return true;
                                    }
                            }

                            if (isset($role)) {
                                    throw new Exception(sprintf("Failed acquire role %s", $role), Exception::ROLE);
                            } else {
                                    return true;
                            }
                    });
        }

        /**
         * Check object action.
         * 
         * @param string $action The model action.
         * @param Exam $model The model object.
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
                            // Refuse to delete a non-deletable exam:
                            // 
                            if ($action == self::DELETE && $model->getState()->has(State::DELETABLE) == false) {
                                    throw new Exception("It's not allowed to delete this exam", Exception::ACTION);
                            }

                            return true;
                    });
        }

}
