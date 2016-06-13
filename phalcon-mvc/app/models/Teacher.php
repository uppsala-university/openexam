<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Teacher.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

/**
 * The teacher model.
 * 
 * Represents a user having the teacher role.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Teacher extends Role
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
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'teachers';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'   => 'id',
                        'user' => 'user'
                );
        }

}
