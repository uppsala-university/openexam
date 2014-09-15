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
                $this->hasMany('id', 'OpenExam\Models\Computer', 'room_id', array('alias' => 'Computers'));
        }

        public function getSource()
        {
                return 'rooms';
        }

}
