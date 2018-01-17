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
// File:    File.php
// Created: 2014-09-21 17:23:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\Remove;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Answer as AnswerModelGuard;

/**
 * The file model.
 * 
 * Represent an file uploaded as part of an question answer.
 * 
 * @property Answer $answer The related answer.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class File extends ModelBase
{

        /**
         * Guard against problematic methods use.
         */
        use AnswerModelGuard;

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The answer ID.
         * @var integer
         */
        public $answer_id;
        /**
         * The file name (descriptive).
         * @var string
         */
        public $name;
        /**
         * The file path.
         * @var string
         */
        public $path;
        /**
         * The MIME type (e.g. video).
         * @var string
         */
        public $type;
        /**
         * The MIME subtype (e.g. pdf).
         * @var string
         */
        public $subtype;

        public function initialize()
        {
                parent::initialize();

                $this->belongsTo("answer_id", "OpenExam\Models\Answer", "id", array(
                        "foreignKey" => true,
                        "alias"      => 'answer'
                ));

                $this->addBehavior(new Remove(array(
                        'beforeSave' => array(
                                'field'  => 'path',
                                'search' => $this->getDI()->getConfig()->application->baseUri
                        )
                    )
                ));

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('name', 'path', 'type', 'subtype'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('name', 'path', 'type', 'subtype'),
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
                return 'files';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'        => 'id',
                        'answer_id' => 'answer_id',
                        'name'      => 'name',
                        'path'      => 'path',
                        'type'      => 'type',
                        'subtype'   => 'subtype'
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
                    array(
                        'answer_id', 'name'
                    ), new Uniqueness(
                    array(
                        'message' => "This answer already has an file named $this->name"
                    )
                ));
                $validator->add(
                    array(
                        'answer_id', 'path'
                    ), new Uniqueness(
                    array(
                        'message' => "This answer already has an file at this location"
                    )
                ));

                return $this->validate($validator);
        }

}
