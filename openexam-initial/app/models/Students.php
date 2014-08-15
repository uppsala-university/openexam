<?php

namespace OpenExam\Models;

class Students extends ModelBase
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
                $this->hasMany("id", "Answers", "student_id", NULL);
                $this->belongsTo("exam_id", "Exams", "id", array("foreignKey" => true));
        }

        public function getSource()
        {
                return 'students';
        }

}
