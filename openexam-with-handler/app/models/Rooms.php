<?php

namespace OpenExam\Models;

class Rooms extends ModelBase
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
        public $name;
        /**
         *
         * @var string
         */
        public $description;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "Computers", "room_id", NULL);
        }

        public function getSource()
        {
                return 'rooms';
        }

}
