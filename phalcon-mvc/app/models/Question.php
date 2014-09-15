<?php

namespace OpenExam\Models;

/**
 * @property array $users The question correctors.
 */
class Question extends ModelBase
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
         * @var integer
         */
        public $topic_id;
        /**
         *
         * @var double
         */
        public $score;
        /**
         *
         * @var string
         */
        public $name;
        /**
         *
         * @var string
         */
        public $quest;
        /**
         *
         * @var array
         */
        public $user;
        /**
         *
         * @var string
         */
        public $status;
        /**
         *
         * @var string
         */
        public $comment;
        /**
         *
         * @var string
         */
        public $grades;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Answer', 'question_id', array('alias' => 'Answers'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
                $this->belongsTo('topic_id', 'OpenExam\Models\Topic', 'id', array('foreignKey' => true, 'alias' => 'Topic'));
        }

        public function beforeCreate()
        {
                $this->status = 'active';
        }

        public function beforeSave()
        {
                $this->user = json_encode($this->users);
        }

        public function afterFetch()
        {
                $this->users = json_decode($this->user);
        }

        public function getSource()
        {
                return 'questions';
        }

}
