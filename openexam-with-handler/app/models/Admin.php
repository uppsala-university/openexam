<?php

namespace OpenExam\Models;

class Admin extends ModelBase
{

        /**
         *
         * @var integer
         */
        public $id;
        /**
         *
         * @var string
         */
        public $user;

        public function getSource()
        {
                return 'admins';
        }

}
