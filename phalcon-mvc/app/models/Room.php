<?php

namespace OpenExam\Models;

class Room extends ModelBase
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
                $this->hasMany("id", "Computer", "room_id", NULL);
        }

        public function getSource()
        {
                return 'rooms';
        }

}
