<?php

namespace OpenExam\Models;

/**
 * The answer model.
 * 
 * @property Result $Result The related result.
 * @property Question $Question The related question.
 * @property Student $Student The related student.
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
        public $answered;
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

        public function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Result', 'answer_id', array('alias' => 'Result'));
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array('foreignKey' => true, 'alias' => 'Question'));
                $this->belongsTo('student_id', 'OpenExam\Models\Student', 'id', array('foreignKey' => true, 'alias' => 'Student'));
        }

        /**
         * Called before model is created.
         */
        public function beforeCreate()
        {
                $this->answered = false;
        }

        /**
         * Called before model is saved.
         */
        public function beforeSave()
        {
                $this->answered = $this->answered ? 'Y' : 'N';
        }

        /**
         * Called after the model was read.
         */
        public function afterFetch()
        {
                $this->answered = $this->answered == 'Y';
        }

        public function getSource()
        {
                return 'answers';
        }

}
