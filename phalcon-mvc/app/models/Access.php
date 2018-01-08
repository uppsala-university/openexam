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
// File:    Access.php
// Created: 2014-12-15 15:43:45
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * The access definition model.
 * 
 * This class defines an IP-address restriction for an exam. The definition
 * in addr contains either:
 * 
 * <ul>
 * <li>An single IP-address.</li>
 * <li>An range of IP-addresses (e.g. 192.168.4.15 - 192.168.4.60).</li>
 * <li>Network addresses using subnet mask or CIDR-notation.</li>
 * </ul>
 * 
 * Notice:
 * -----------
 * 1. The address range are allowed to cross subnet boundaries (e.g. multiple 
 *    class C: 192.168.4.1 - 192.168.6.255).
 * 
 * Example:
 * -----------
 * <code>
 * $access->name = "BMC;Tentasalar;B1:3";
 * 
 * $access->addr = "192.168.25.67";                     // Single IP-address.
 * $access->addr = "192.168.25.0/26";                   // CIDR-notation.
 * $access->addr = "192.168.25.0/255.255.255.192";      // Subnet-notation.
 * $access->addr = "192.168.25.1-192.168.25.63";        // Address range.
 * </code>
 * 
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Access extends ModelBase
{

        /**
         * Guard against problematic methods use.
         */
        use ExamModelGuard;

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * The access restriction name (semi-colon separated string).
         * @var string
         */
        public $name;
        /**
         * The IP address definition.
         * @var string
         */
        public $addr;

        protected function initialize()
        {
                parent::initialize();

                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'exam',
                        'reusable'   => true
                ));

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('name', 'addr'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('name', 'addr'),
                                'value' => null
                        )
                )));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'access';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'      => 'id',
                        'exam_id' => 'exam_id',
                        'name'    => 'name',
                        'addr'    => 'addr'
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
                    array('exam_id', 'addr'), new Uniqueness(
                    array(
                        'message' => "The address $this->addr is already in use on this exam."
                    )
                ));
                $validator->add(
                    array('exam_id', 'name'), new Uniqueness(
                    array(
                        'message' => "The name $this->name is already in use on this exam."
                    )
                ));

                return $this->validate($validator);
        }

}
