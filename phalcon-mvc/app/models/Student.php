<?php

namespace OpenExam\Models;

/**
 * The student model.
 * 
 * Represents a user having the student role. The student can have an 
 * associated tag. It's usually used for storing miscellanous data that can 
 * be used in the result report process.
 * 
 * @property Answer[] $answers Answers related to this student.
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Student extends Role
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;
        /**
         * The student code (anonymous).
         * @var string
         */
        public $code;
        /**
         * Generic tag for this student (e.g. course).
         * @var string
         */
        public $tag;

        protected function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Answer', 'student_id', array('alias' => 'answers'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'exam'));
        }

        public function getSource()
        {
                return 'students';
        }

}
