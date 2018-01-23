<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Room.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\FilterText;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

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

        public function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Computer', 'room_id', array(
                        'alias'    => 'computers',
                        'reusable' => true
                ));

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('name', 'description'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('name', 'description'),
                                'value' => null
                        )
                )));

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

        /**
         * Validates business rules.
         * @return boolean
         */
        public function validation()
        {
                $validator = new Validation();

                $validator->add(
                    'name', new Uniqueness(
                    array(
                        'message' => "The name already exists"
                    )
                ));

                return $this->validate($validator);
        }

}
