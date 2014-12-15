<?php

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model\Validator\Inclusionin;

/**
 * The lock model.
 * 
 * Represent the lock aquired for a single computer on an exam.
 * 
 * @property Student $student The student this lock was aquired for.
 * @property Computer $computer The computer that aquired the lock.
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
         * Date/time when the lock was aquired.
         * @var string
         */
        public $aquired;
        /**
         * The lock status (see STATUS_XXX).
         * @var string 
         */
        public $status;

        protected function initialize()
        {
                parent::initialize();

                $this->belongsTo('student_id', 'OpenExam\Models\Student', 'id', array('foreignKey' => true, 'alias' => 'student'));
                $this->belongsTo('computer_id', 'OpenExam\Models\Computer', 'id', array('foreignKey' => true, 'alias' => 'computer'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'exam'));

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'aquired',
                                'format' => 'Y-m-d H:i:s'
                        )
                )));
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(array(
                        'field'  => 'status',
                        'domain' => array(self::STATUS_PENDING, self::STATUS_APPROVED)
                )));
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

        public function getSource()
        {
                return 'locks';
        }

}
