<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
                                    if ($user->roles->acquire($role, $model->exam_id)) {
                                            return true;
                                    }
                            } elseif ($role == Roles::CORRECTOR) {
                                    foreach ($model->exam->questions as $question) {
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
                                            if ($user->roles->acquire($role, $model->exam_id)) {
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
                                                            if ($user->roles->acquire($role, $question->id)) {
                                                                    return true;
                                                            }
                                                    }
                                            } else {
                                                    if ($user->roles->acquire($role, $model->exam_id)) {
                                                            return true;
                                                    }
                                            }
                                    } elseif ($model->shared == Resource::SHARED_GROUP) {
                                            // 
                                            // Check groups in most to least specific order:
                                            // 
                                            if (($group = $model->exam->workgroup)) {
                                                    return $this->user->getPrimaryGroup() == $group;
                                            }
                                            if (($group = $model->exam->department)) {
                                                    return $this->user->getPrimaryGroup() == $group;
                                            }
                                            if (($group = $model->exam->division)) {
                                                    return $this->user->getPrimaryGroup() == $group;
                                            }
                                    } elseif ($model->shared == Resource::SHARED_GLOBAL) {
                                            return true;
                                    } elseif ($model->user == $user->getPrincipalName()) {
                                            return true;        // Permit personal access
                                    }
                            } elseif ($action == self::UPDATE || $action == self::DELETE) {
                                    if ($model->user == $user->getPrincipalName()) {
                                            return true;
                                    }
                            } elseif ($action == self::CREATE) {
                                    if ($user->roles->acquire($role, $model->exam_id)) {
                                            return true;
                                    }
                            }

                            if (isset($role)) {
                                    throw new Exception(sprintf("You are not allowed to read resource %s", $model->name), Exception::ACCESS);
                            } else {
                                    return true;
                            }
                    });
        }

}
