<?php

namespace OpenExam\Models;

/**
 * The invigilator model.
 * 
 * Represents a user having the invigilator role.
 * 
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Invigilator extends Role
{

        /**
         * This object ID.
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

        protected function initialize()
        {
                parent::initialize();
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'exam'));
        }

        public function getSource()
        {
                return 'invigilators';
        }

}
