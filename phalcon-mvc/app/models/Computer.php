<?php

namespace OpenExam\Models;

class Computer extends ModelBase
{

        /**
         *
         * @var integer
         */
        public $id;
        /**
         *
         * @var integer
         */
        public $room_id;
        /**
         *
         * @var string
         */
        public $hostname;
        /**
         *
         * @var string
         */
        public $ipaddr;
        /**
         *
         * @var integer
         */
        public $port;
        /**
         *
         * @var string
         */
        public $password;
        /**
         *
         * @var string
         */
        public $created;
        /**
         *
         * @var string
         */
        public $updated;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "Locks", "computer_id", NULL);
                $this->belongsTo("room_id", "Room", "id", array("foreignKey" => true));
        }

        public function getSource()
        {
                return 'computers';
        }

}
