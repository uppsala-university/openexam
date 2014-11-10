<?php

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

        public function getSource()
        {
                return 'teachers';
        }

}
