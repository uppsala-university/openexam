<?php

class Rooms extends \Phalcon\Mvc\Model
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
                $this->hasMany("id", "Computers", "room_id", NULL);
        }

}
