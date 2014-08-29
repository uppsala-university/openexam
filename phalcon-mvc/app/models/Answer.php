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
         * @var string
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
                $this->hasMany("id", "Results", "answer_id", NULL);
                $this->belongsTo("question_id", "Question", "id", array("foreignKey" => true));
                $this->belongsTo("student_id", "Student", "id", array("foreignKey" => true));
        }

        public function getSource()
        {
                return 'answers';
        }

}
