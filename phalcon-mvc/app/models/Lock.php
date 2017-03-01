<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Lock.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model\Validator\Inclusionin;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * The lock model.
 * 
 * Represent the lock acquired for a single computer on an exam.
 * 
 * @property Student $student The student this lock was acquired for.
 * @property Computer $computer The computer that acquired the lock.
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Lock extends ModelBase
{

        /**
         * The lock is pending (waiting for approval).
         */
        const STATUS_PENDING = 'pending';
        /**
         * The lock has been approved (manual or automatic).
         */
        const STATUS_APPROVED = 'approved';

        /**
         * This object ID.
         * @var integer
         */
        public $id;
        /**
         * The student ID.
         * @var integer
         */
        public $student_id;
        /**
         * The computer ID.
         * @var integer
         */
        public $computer_id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * Date/time when the lock was acquired.
         * @var string
         */
        public $acquired;
        /**
         * The lock status (see STATUS_XXX).
         * @var string 
         */
        public $status;

        protected function initialize()
        {
                parent::initialize();

                $this->belongsTo('student_id', 'OpenExam\Models\Student', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'student'
                ));
                $this->belongsTo('computer_id', 'OpenExam\Models\Computer', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'computer',
                        'reusable'   => true
                ));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'exam',
                        'reusable'   => true
                ));

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'acquired',
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
                return 'locks';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'          => 'id',
                        'student_id'  => 'student_id',
                        'computer_id' => 'computer_id',
                        'exam_id'     => 'exam_id',
                        'acquired'    => 'acquired',
                        'status'      => 'status'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(
                    array(
                        'field'  => 'status',
                        'domain' => array(self::STATUS_PENDING, self::STATUS_APPROVED)
                    )
                ));
                $this->validate(new Uniqueness(
                    array(
                        'field'   => array('student_id', 'computer_id', 'exam_id'),
                        'message' => "The exam lock already exist"
                    )
                ));
                
                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->status)) {
                        $this->status = self::STATUS_APPROVED;
                }
        }

}
