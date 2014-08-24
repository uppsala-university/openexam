<?php

namespace OpenExam\Models;

class Exam extends ModelBase
{

        /**
         *
         * @var integer
         */
        public $id;
        /**
         *
         * @var string
         */
        public $name;
        /**
         *
         * @var string
         */
        public $descr;
        /**
         *
         * @var string
         */
        public $starttime;
        /**
         *
         * @var string
         */
        public $endtime;
        /**
         *
         * @var string
         */
        public $created;
        /**
         *
         * @var string
         */
        public $updated;
        /**
         *
         * @var string
         */
        public $creator;
        /**
         *
         * @var integer
         */
        public $details;
        /**
         *
         * @var string
         */
        public $decoded;
        /**
         *
         * @var string
         */
        public $orgunit;
        /**
         *
         * @var string
         */
        public $grades;
        /**
         *
         * @var string
         */
        public $testcase;
        /**
         *
         * @var string
         */
        public $lockdown;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "Contributor", "exam_id", NULL);
                $this->hasMany("id", "Decoder", "exam_id", NULL);
                $this->hasMany("id", "Examinator", "exam_id", NULL);
                $this->hasMany("id", "Lock", "exam_id", NULL);
                $this->hasMany("id", "Question", "exam_id", NULL);
                $this->hasMany("id", "Student", "exam_id", NULL);
        }

        public function getSource()
        {
                return 'exams';
        }

}
