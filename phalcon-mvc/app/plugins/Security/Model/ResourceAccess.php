<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ResourceAccess.php
// Created: 2014-09-30 14:52:39
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Models\Resource;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * Access control for the Resource model.
 * 
 * Assumption: 
 * 
 * This model is an abstraction of the "media" library. The resources defined 
 * here can be inserted in questions. The permitted action are already defined 
 * by ACL.
 * 
 * Create, update and delete of resources are always done in the context of 
 * an specific exam. Read access are defined by the sharing level. 
 * 
 * For students, the resource has to be connected with their exam for being 
 * accessable.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResourceAccess extends ObjectAccess
{

        /**
         * Check object role.
         * 
         * @param string $action The model action.
         * @param Resource $model The model object.
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
                                    foreach ($model->exam->questions as $question) {
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
                    });
        }

        /**
         * Check object action.
         * 
         * @param string $action The model action.
         * @param Resource $model The model object.
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
                            // Student can read any resource connected with the exam:
                            // 
                            if ($role == Roles::STUDENT) {
                                    if ($action == self::READ) {
                                            if ($user->roles->aquire($role, $model->exam_id)) {
                                                    return true;
                                            }
                                    }
                            }

                            // 
                            // Here we make a distinction between the action modes. 
                            // 
                            // 1. Only allow the resource publisher to modify or delete 
                            //    a resource. 
                            // 2. All staff members can publish (create) new resources. 
                            // 3. Control of the sharing level is enforced when reading 
                            //    a resource.
                            // 
                            if ($action == self::READ) {
                                    if ($model->shared == Resource::NOT_SHARED) {
                                            if ($model->user == $user->getPrincipalName()) {
                                                    return true;
                                            }
                                    } elseif ($model->shared == Resource::SHARED_EXAM) {
                                            if (Roles::isGlobal($role)) {
                                                    return true;
                                            }
                                            if ($role == Roles::CORRECTOR) {
                                                    if ($user->roles->hasRole($role, $model->exam_id)) {
                                                            return true;
                                                    }
                                                    foreach ($model->exam->questions as $question) {
                                                            if ($user->roles->aquire($role, $question->id)) {
                                                                    return true;
                                                            }
                                                    }
                                            } else {
                                                    if ($user->roles->aquire($role, $model->exam_id)) {
                                                            return true;
                                                    }
                                            }
                                    } elseif ($model->shared == Resource::SHARED_GROUP) {
                                            // TODO: depends on directory service and caller having the same primary group.
                                    } elseif ($model->shared == Resource::SHARED_GLOBAL) {
                                            return true;
                                    }
                            } elseif ($action == self::UPDATE || $action == self::DELETE) {
                                    if ($model->user == $user->getPrincipalName()) {
                                            return true;
                                    }
                            } elseif ($action == self::CREATE) {
                                    if ($user->roles->aquire($role, $model->exam_id)) {
                                            return true;
                                    }
                            }

                            if (isset($role)) {
                                    throw new Exception('access');
                            } else {
                                    return true;
                            }
                    });
        }

}
