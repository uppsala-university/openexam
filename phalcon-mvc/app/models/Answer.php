<?php

namespace OpenExam\Models;

class Answer extends ModelBase
{

        /**
         *
         * @var integer
         */
        public $id;
        /**
         *
         * @var integer
         */
        public $question_id;
        /**
         *
         * @var integer
         */
        public $student_id;
        /**
         *
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

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Result', 'answer_id', array('alias' => 'Result'));
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array('foreignKey' => true, 'alias' => 'Question'));
                $this->belongsTo('student_id', 'OpenExam\Models\Student', 'id', array('foreignKey' => true, 'alias' => 'Student'));
        }

        public function beforeCreate()
        {
                $this->answered = false;
        }

        public function beforeSave()
        {
                $this->answered = $this->answered ? 'Y' : 'N';
        }

        public function afterFetch()
        {
                $this->answered = $this->answered == 'Y';
        }

        public function getSource()
        {
                return 'answers';
        }

}
