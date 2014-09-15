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
         * @var array
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
                $this->hasMany('id', 'OpenExam\Models\Contributor', 'exam_id', array('alias' => 'Contributors'));
                $this->hasMany('id', 'OpenExam\Models\Decoder', 'exam_id', array('alias' => 'Decoders'));
                $this->hasMany('id', 'OpenExam\Models\Invigilator', 'exam_id', array('alias' => 'Invigilators'));
                $this->hasMany('id', 'OpenExam\Models\Lock', 'exam_id', array('alias' => 'Locks'));
                $this->hasMany('id', 'OpenExam\Models\Question', 'exam_id', array('alias' => 'Questions'));
                $this->hasMany('id', 'OpenExam\Models\Student', 'exam_id', array('alias' => 'Students'));
        }

        public function beforeSave()
        {
                $this->grades = json_encode($this->grades);
        }

        public function afterFetch()
        {
                $this->grades = json_decode($this->grades);
        }

        public function getSource()
        {
                return 'exams';
        }

}
