<?php

class Questions extends \Phalcon\Mvc\Model
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
         * @var string
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
                $this->hasMany("id", "Answers", "question_id", NULL);
                $this->belongsTo("exam_id", "Exams", "id", array("foreignKey" => true));
                $this->belongsTo("topic_id", "Topics", "id", array("foreignKey" => true));
        }

}
