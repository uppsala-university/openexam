<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Admin.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

/**
 * The admin model.
 * 
 * Represents a user having the admin role.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Admin extends Role
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;

        /**
         * Return source table name.
         * @return string
         */
        public function getSource()
        {
                return 'admins';
        }

}
