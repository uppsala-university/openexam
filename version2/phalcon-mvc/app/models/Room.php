<?php

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\FilterText;

/**
 * The room model.
 * 
 * @property Computer[] $computers Computers that belongs to this room.
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

        protected function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Computer', 'room_id', array(
                        'alias' => 'computers'
                ));

                // 
                // TODO: better do filtering on client side.
                // 
                $this->addBehavior(new FilterText(array(
                        'beforeValidationOnCreate' => array(
                                'fields' => 'description'
                        ),
                        'beforeValidationOnUpdate' => array(
                                'fields' => 'description'
                        )
                    )
                ));
        }

        public function getSource()
        {
                return 'rooms';
        }

}
