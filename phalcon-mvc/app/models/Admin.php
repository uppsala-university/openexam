<?php

namespace OpenExam\Models;

/**
 * The admin model.
 * 
 * Represents a user having the admin role.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Admin extends ModelBase
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
