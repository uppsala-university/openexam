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
// File:    Topic.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Generate\Maximum;
use OpenExam\Library\Model\Behavior\Generate\Unique;
use OpenExam\Library\Model\Behavior\Generate\UUID;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * Question topic.
 * 
 * A topic is a grouping of questions. It has a $randomize property that 
 * defines if that many questions should be randomized from this topic
 * or not (if 0).
 * 
 * The $grades property contains optional conditions that (if present) 
 * gets used in evaluation of the grade for a student in this particular 
 * topic. The $grades property might contain e.g. source code. Its intended 
 * to be used for implementing symbolic grades on an exam.
 * 
 * The $depend property contains optional source code that (if present) 
 * is used for determine whether the caller (the student) is allowed to 
 * proceed into this topic. The $depend property might contain e.g. source
 * code. Its intended to be used for implementing dependecies between 
 * section in exam.
 * 
 * @property Question[] $questions Questions related to this topic.
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Topic extends ModelBase
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
         * The slot (ordering) of this object.
         * @var integer 
         */
        public $slot;
        /**
         * The UUID for this object.
         * @see http://en.wikipedia.org/wiki/Universally_unique_identifier
         * @var string 
         */
        public $uuid;
        /**
         * The topic name.
         * @var string
         */
        public $name;
        /**
         * Number of questions to randomize from this topic.
         * @var integer
         */
        public $randomize;
        /**
         * Source code for answer score evaluation (for this topic).
         * @var string
         */
        public $grades;
        /**
         * Source code for topics dependencies (filter access to this topic).
         * @var string
         */
        public $depend;

        protected function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Question', 'topic_id', array(
                        'alias'    => 'questions',
                        'reusable' => true
                ));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'exam',
                        'reusable'   => true
                ));

                $this->addBehavior(new Maximum(array(
                        'beforeValidationOnCreate' => array(
                                'field' => 'slot',
                                'limit' => 'exam_id'
                        )
                )));
                $this->addBehavior(new Unique(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'name',
                                'limit'  => 'exam_id',
                                'format' => 'TCSN%d'
                        )
                )));
                $this->addBehavior(new UUID(array(
                        'beforeCreate' => array(
                                'field' => 'uuid',
                                'force' => true
                        ),
                        'beforeUpdate' => array(
                                'field' => 'uuid',
                                'force' => false
                        )
                )));

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('uuid', 'name', 'grades', 'depend'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('uuid', 'name', 'grades', 'depend'),
                                'value' => null
                        )
                )));
        }

        public function validation()
        {
                if (defined('VALIDATION_SKIP_UNIQUENESS_CHECK')) {
                        return true;
                }

                $validator = new Validation();

                if ($this->id == 0) {
                        $validator->add(
                            array(
                                "name", "exam_id"
                            ), new Uniqueness(
                            array(
                                "message" => "This topic name has already been added on exam"
                            )
                        ));
                }
                if ($this->id == 0) {
                        $validator->add(
                            array(
                                "slot", "exam_id"
                            ), new Uniqueness(
                            array(
                                'message' => "This topic slot has already been added on exam"
                            )
                        ));
                }

                return $this->validate($validator);
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->randomize)) {
                        $this->randomize = 0;
                }
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'topics';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'        => 'id',
                        'exam_id'   => 'exam_id',
                        'slot'      => 'slot',
                        'uuid'      => 'uuid',
                        'name'      => 'name',
                        'randomize' => 'randomize',
                        'grades'    => 'grades',
                        'depend'    => 'depend'
                );
        }

}
