<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Room.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\FilterText;

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
                        'alias'    => 'computers',
                        'reusable' => true
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

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'rooms';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'          => 'id',
                        'name'        => 'name',
                        'description' => 'description'
                );
        }

}
