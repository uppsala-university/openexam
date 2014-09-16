<?php

namespace OpenExam\Models;

/**
 * Question topic.
 * 
 * A topic is a grouping of questions. It has a $randomize property that 
 * defines if that many questions should be randomized from this topic
 * or not (if 0).
 * 
 * The $grades property contains optional conditions that (if present) 
 * gets used in evaluation of the grade for a student in this particular 
 * topic. The $grades property might contain e.g. source code. Its intended 
 * to be used for implementing symbolic grades on an exam.
 * 
 * The $depend property contains optional source code that (if present) 
 * is used for determine whether the caller (the student) is allowed to 
 * proceed into this topic. The $depend property might contain e.g. source
 * code. Its intended to be used for implementing dependecies between 
 * section in exam.
 * 
 * @property Question $Questions Questions related to this topic.
 * @property Exam $Exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Topic extends ModelBase
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
         * The topic name.
         * @var string
         */
        public $name;
        /**
         * Number of questions to randomize from this topic.
         * @var integer
         */
        public $randomize;
        /**
         * Source code for answer score evaluation (for this topic).
         * @var string
         */
        public $grades;
        /**
         * Source code for topics dependencies (filter access to this topic).
         * @var string
         */
        public $depend;

        protected function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Question', 'topic_id', array('alias' => 'Questions'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->randomize)) {
                        $this->randomize = 0;
                }
        }

        public function getSource()
        {
                return 'topics';
        }

}
