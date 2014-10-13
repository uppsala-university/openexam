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
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResourceAccess extends ObjectAccess
{

        /**
         * Check model access.
         * @param string $action The model action.
         * @param Resource $model The model.
         * @param User $user The peer object.
         */
        public function checkAccess($action, $model, $user)
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

                /**
                 * Assumption: 
                 * 
                 * This model is an abstraction of the "media" library. The
                 * resources defined here can be inserted in questions. The 
                 * permitted action are already defined by ACL.
                 * 
                 * Create, update and delete of resources are always done in 
                 * the context of an specific exam. Read access are defined 
                 * by the sharing level. 
                 * 
                 * For students, the resource has to be connected with their 
                 * exam for being accessable.
                 */
                // 
                // Handle student access separate:
                // 
                if ($role == Roles::STUDENT) {
                        if ($user->roles->aquire($role, $model->exam_id)) {
                                $user->setPrimaryRole($role);
                                return true;
                        } else {
                                $user->setPrimaryRole($role);
                                throw new Exception('access');
                        }
                }

                // 
                // Here we make a distinction between the action modes. Only
                // allow the resource publisher to modify or delete it. All 
                // staff members can publish (create) new resources. Control
                // of the sharing level is enforced when reading resource.
                // 
                if ($action == self::READ) {
                        if ($model->shared == Resource::NOT_SHARED) {
                                if ($model->user != $user->getPrincipalName()) {
                                        $user->setPrimaryRole($role);
                                        throw new Exception('access');
                                }
                        } elseif ($model->shared == Resource::SHARED_EXAM) {
                                if ($role == Roles::CONTRIBUTOR ||
                                    $role == Roles::CREATOR ||
                                    $role == Roles::DECODER ||
                                    $role == Roles::INVIGILATOR) {
                                        if ($user->roles->aquire($role, $model->exam_id)) {
                                                $user->setPrimaryRole($role);
                                                return true;
                                        }
                                } elseif ($role == Roles::CORRECTOR) {
                                        foreach ($model->exam->questions as $question) {
                                                if ($user->roles->aquire($role, $question->id)) {
                                                        $user->setPrimaryRole($role);
                                                        return true;
                                                }
                                        }
                                }
                        } elseif ($model->shared == Resource::SHARED_GROUP) {
                                // TODO: depends on directory service and caller having the same primary group.
                        } elseif ($model->shared == Resource::SHARED_GLOBAL) {
                                $user->setPrimaryRole($role);
                                return true;
                        }
                } elseif ($action == self::UPDATE || $action == self::DELETE) {
                        if ($model->user == $user->getPrincipalName()) {
                                $user->setPrimaryRole($role);
                                return true;
                        }
                } elseif ($action == self::CREATE) {
                        if ($user->roles->aquire($role, $model->exam_id)) {
                                $user->setPrimaryRole($role);
                                return true;
                        }
                }
        }

}
