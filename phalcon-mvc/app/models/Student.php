<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
use OpenExam\Library\Model\Validation\Sequence as SequenceValidator;
use Phalcon\Mvc\Model\Validator\Regex as RegexValidator;
use Phalcon\Mvc\Model\Validator\Uniqueness;

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

                $this->validate(new Uniqueness(
                    array(
                        "field"   => array("code", "exam_id"),
                        "message" => "The code '$this->code' is already in use on this exam"
                    )
                ));
                $this->validate(new RegexValidator(
                    array(
                        "field"   => "code",
                        "message" => "The anonymous code '$this->code' is not matching expected format",
                        "pattern" => Pattern::get(Pattern::MATCH_CODE)
                    )
                ));
                $this->validate(new SequenceValidator(
                    array(
                        "field"   => array("starttime", "endtime"),
                        "message" => "Start time can't come after end time",
                        "type"    => "datetime"
                    )
                ));

                return $this->validationHasFailed() != true;
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->persnr = $this->getAttribute(Principal::ATTR_PNR);
                parent::afterFetch();
        }

}
