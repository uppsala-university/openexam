<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    FileAccess.php
// Created: 2014-09-30 14:50:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Logger\Adapter\File;

/**
 * Access control for the File model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class FileAccess extends ObjectAccess
{

        /**
         * Check object role.
         * 
         * @param string $action The model action.
         * @param File $model The model object.
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
                                    if ($user->roles->aquire($role, $model->answer->question->exam_id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::STUDENT) {
                                    if ($user->roles->aquire($role, $model->answer->student->exam_id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::CORRECTOR) {
                                    if ($user->roles->aquire($role, $model->answer->question->id)) {
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
         * @param File $model The model object.
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
                            // Students can only access their own files:
                            // 
                            if ($action != self::CREATE) {
                                    if ($role == Roles::STUDENT) {
                                            if ($model->answer->student->user != $user->getPrincipalName()) {
                                                    throw new Exception('owner');
                                            }
                                    }
                            }

                            return true;
                    });
        }

}
