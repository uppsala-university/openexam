<?php

class Exams extends \Phalcon\Mvc\Model
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
                $this->hasMany("id", "Contributors", "exam_id", NULL);
                $this->hasMany("id", "Decoders", "exam_id", NULL);
                $this->hasMany("id", "Examinators", "exam_id", NULL);
                $this->hasMany("id", "Locks", "exam_id", NULL);
                $this->hasMany("id", "Questions", "exam_id", NULL);
                $this->hasMany("id", "Students", "exam_id", NULL);
        }

}
