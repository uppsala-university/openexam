<?php

namespace OpenExam\Models;

class Teacher extends ModelBase
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
                return 'teachers';
        }

}
