<?php

namespace OpenExam\Models;

/**
 * The lock model.
 * 
 * Represent the lock aquired for a single computer on an exam.
 * 
 * @property Computer $Computer The computer that aquired the lock.
 * @property Exam $Exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Lock extends ModelBase
{

        /**
         * This object ID.
         * @var integer
         */
        public $id;
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

        public function initialize()
        {
                parent::initialize();
                $this->belongsTo('computer_id', 'OpenExam\Models\Computer', 'id', array('foreignKey' => true, 'alias' => 'Computer'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
        }

        /**
         * Called before the model is created.
         */
        public function beforeCreate()
        {
                $this->aquired = date('Y-m-d H:i:s');
        }

        public function getSource()
        {
                return 'locks';
        }

}
