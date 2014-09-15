<?php

namespace OpenExam\Models;

/**
 * The room model.
 * 
 * @property Computer $Computers Computers that belongs to this room.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Room extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The room name.
         * @var string
         */
        public $name;
        /**
         * The room description.
         * @var string
         */
        public $description;

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
