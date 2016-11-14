<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Database.php
// Created: 2016-11-13 23:41:55
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute\Storage;

use OpenExam\Library\Catalog\Attribute\Storage\Backend;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Models\User;

/**
 * Database storage backend.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Database implements Backend
{

        /**
         * Check if user exist.
         * @param string $principal The user principal name.
         * @return boolean 
         */
        public function exists($principal)
        {
                return User::count(array(
                            'condition' => 'principal = :principal:',
                            'bind'      => array(
                                    'principal' => $principal
                            )
                    )) > 0;
        }

        /**
         * Insert user attributes.
         * @param User $user The user model.
         */
        public function insert($user)
        {
                if (!$user->save()) {
                        throw new Exception($user->getMessages()[0]);
                }
        }

        /**
         * Delete user.
         * @param string $principal The user principal name.
         */
        public function delete($principal)
        {
                if (($user = User::find(array(
                            'condition' => 'principal = :principal:',
                            'bind'      => array(
                                    'principal' => $principal
                            )
                    ))) != null) {
                        if (!$user->delete()) {
                                throw new Exception($user->getMessages()[0]);
                        }
                }
        }

}
