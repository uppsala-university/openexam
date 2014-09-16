<?php

namespace OpenExam\Models;

/**
 * The answer model.
 * 
 * @property Result $result The related result.
 * @property Question $question The related question.
 * @property Student $student The related student.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Answer extends ModelBase
{

        /**
         * This object ID.
         * @var integer
         */
        public $id;
        /**
         * The question ID.
         * @var integer
         */
        public $question_id;
        /**
         * The student ID.
         * @var integer
         */
        public $student_id;
        /**
         * Is already answered?
         * @var bool
         */
        public $answered = false;
        /**
         *
         * @var string
         */
        public $answer;
        /**
         *
         * @var string
         */
        public $comment;

        protected function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Result', 'answer_id', array('alias' => 'Result'));
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array('foreignKey' => true, 'alias' => 'Question'));
                $this->belongsTo('student_id', 'OpenExam\Models\Student', 'id', array('foreignKey' => true, 'alias' => 'Student'));
        }

        /**
         * Called before model is saved.
         */
        protected function beforeSave()
        {
                $this->answered = $this->answered ? 'Y' : 'N';
        }

        /**
         * Called after model is saved.
         */
        protected function afterSave()
        {
                $this->answered = $this->answered == 'Y';
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->answered = $this->answered == 'Y';
        }

        public function getSource()
        {
                return 'answers';
        }

}
