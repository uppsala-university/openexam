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
// File:    Student.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Core\Pattern;
use OpenExam\Library\Model\Behavior\Student as StudentBehavior;
use OpenExam\Library\Model\Behavior\Transform\DateTimeNull;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
use OpenExam\Library\Model\Validation\DateTime as DateTimeValidator;
use OpenExam\Library\Model\Validation\Sequence as SequenceValidator;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Regex as RegexValidator;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * The student model.
 * 
 * Represents a user having the student role. The student can have an 
 * associated tag. It's usually used for storing miscellanous data that can 
 * be used in the result report process.
 * 
 * @property Answer[] $answers Answers related to this student.
 * @property Exam $exam The related exam.
 * @property Lock[] $locks Active computer/exam locks for this student.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Student extends Role
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
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;
        /**
         * The student code (anonymous).
         * @var string
         */
        public $code;
        /**
         * Generic tag for this student (e.g. course).
         * @var string
         */
        public $tag;
        /**
         * Does this student needs an enquiry?
         * @var bool 
         */
        /**
         * Override start time defined exam object for this student.
         * @var string 
         */
        public $starttime;
        /**
         * Override end time defined exam object for this student.
         * @var string 
         */
        public $endtime;
        /**
         * Set finished time (after saved, the exam can't be opened again).
         * @var string 
         */
        public $finished;
        /**
         * The student personal number.
         * @var string 
         */
        public $persnr;

        protected function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Answer', 'student_id', array(
                        'alias' => 'answers'
                ));
                $this->hasMany('id', 'OpenExam\Models\Lock', 'student_id', array(
                        'alias'    => 'locks',
                        'reusable' => true
                ));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'exam',
                        'reusable'   => true
                ));

                $this->addBehavior(new StudentBehavior(array(
                        'beforeValidationOnCreate' => array(
                                'code' => true,
                                'user' => true
                        )
                )));

                $this->addBehavior(new DateTimeNull(array(
                        'beforeSave' => array(
                                'field'  => array('starttime', 'endtime'),
                                'format' => 'Y-m-d H:i:s'
                        )
                )));

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('user', 'code', 'tag'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('user', 'code', 'tag'),
                                'value' => null
                        )
                )));

                // 
                // Required for datetime validator:
                // 
                $this->keepSnapshots(true);
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'students';
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
                        'user'      => 'user',
                        'code'      => 'code',
                        'tag'       => 'tag',
                        'enquiry'   => 'enquiry',
                        'starttime' => 'starttime',
                        'endtime'   => 'endtime',
                        'finished'  => 'finished'
                );
        }

        public function validation()
        {
                if (parent::validation() == false) {
                        return false;
                }

                if (defined('VALIDATION_SKIP_UNIQUENESS_CHECK')) {
                        return true;
                }

                $validator = new Validation();

                $validator->add(
                    array(
                        "code", "exam_id"
                    ), new Uniqueness(
                    array(
                        "message" => "The code '$this->code' is already in use on this exam"
                    )
                ));
                $validator->add(
                    "code", new RegexValidator(
                    array(
                        "message" => "The anonymous code '$this->code' is not matching expected format",
                        "pattern" => Pattern::get(Pattern::MATCH_CODE)
                    )
                ));
                $validator->add(
                    "timestamp", new SequenceValidator(
                    array(
                        "sequence" => array("starttime", "endtime"),
                        "message"  => "Start time can't come after end time",
                        "type"     => "datetime"
                    )
                ));
                $validator->add(
                    array(
                        "starttime", "endtime"
                    ), new DateTimeValidator(
                    array(
                        "message" => "The datetime value can't be in the past",
                        "current" => $this->getSnapshotData()
                    )
                ));

                return $this->validate($validator);
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->enquiry)) {
                        $this->enquiry = false;
                }
        }

        /**
         * Called before model is saved.
         */
        protected function beforeSave()
        {
                $this->enquiry = $this->enquiry ? 'Y' : 'N';
        }

        /**
         * Called after model is saved.
         */
        protected function afterSave()
        {
                $this->enquiry = $this->enquiry == 'Y';
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->enquiry = $this->enquiry == 'Y';
                $this->persnr = $this->getAttribute(Principal::ATTR_PNR);
                parent::afterFetch();
        }

}
