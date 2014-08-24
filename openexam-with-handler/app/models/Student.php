<?php

namespace OpenExam\Models;

class Student extends ModelBase
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
        public $exam_id;
        /**
         *
         * @var string
         */
        public $user;
        /**
         *
         * @var string
         */
        public $code;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "Answer", "student_id", NULL);
                $this->belongsTo("exam_id", "Exam", "id", array("foreignKey" => true));
        }

        public function getSource()
        {
                return 'students';
        }

}
