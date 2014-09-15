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
         *
         * @var string
         */
        public $tag;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Answer', 'student_id', array('alias' => 'Answers'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
        }

        public function getSource()
        {
                return 'students';
        }

}
