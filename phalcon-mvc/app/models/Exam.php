<?php

namespace OpenExam\Models;

class Exam extends ModelBase
{

        /**
         * Show responsible people for examination.
         */
        const RESULT_EXPOSE_EMPLOYEES = 1;
        /**
         * Include statistics of all students.
         */
        const RESULT_OTHERS_STATISTIC = 2;

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
         * @var bool
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
         * @var bool
         */
        public $testcase;
        /**
         *
         * @var bool
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

        public function beforeCreate()
        {
                $this->created = date('Y-m-d H:i:s');
                $this->details = $this->getDI()->get('config')->result->details;
        }

        public function beforeUpdate()
        {
                $this->updated = date('Y-m-d H:i:s');
        }

        public function beforeSave()
        {
                $this->grades = json_encode($this->grades);
                $this->decoded = $this->decoded ? 'Y' : 'N';
                $this->testcase = $this->testcase ? 'Y' : 'N';
                $this->lockdown = $this->lockdown ? 'Y' : 'N';
        }

        public function afterFetch()
        {
                $this->grades = json_decode($this->grades);
                $this->decoded = $this->decoded == 'Y';
                $this->testcase = $this->testcase == 'Y';
                $this->lockdown = $this->lockdown == 'Y';
        }

        public function getSource()
        {
                return 'exams';
        }

}
