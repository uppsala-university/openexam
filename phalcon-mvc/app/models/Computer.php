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
                $this->hasMany('id', 'OpenExam\Models\Lock', 'computer_id', array('alias' => 'Locks'));
                $this->belongsTo('room_id', 'OpenExam\Models\Room', 'id', array('foreignKey' => true, 'alias' => 'Room'));
        }

        public function beforeCreate()
        {
                $this->created = date('Y-m-d H:i:s');
        }

        public function beforeUpdate()
        {
                $this->updated = date('Y-m-d H:i:s');
        }

        public function getSource()
        {
                return 'computers';
        }

}
