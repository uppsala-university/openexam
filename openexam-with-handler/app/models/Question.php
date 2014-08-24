<?php

namespace OpenExam\Models;

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
        public $video;
        /**
         *
         * @var string
         */
        public $image;
        /**
         *
         * @var string
         */
        public $audio;
        /**
         *
         * @var string
         */
        public $type;
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
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "Answer", "question_id", NULL);
                $this->belongsTo("exam_id", "Exam", "id", array("foreignKey" => true));
                $this->belongsTo("topic_id", "Topic", "id", array("foreignKey" => true));
        }

        public function beforeSave()
        {
                $this->user = json_encode($this->user);
        }

        public function afterFetch()
        {
                $this->user = json_decode($this->user);
        }

        public function getSource()
        {
                return 'questions';
        }

}
