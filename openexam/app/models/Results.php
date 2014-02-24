<?php

class Results extends \Phalcon\Mvc\Model
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
        public $answer_id;
        /**
         *
         * @var double
         */
        public $score;
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
                $this->belongsTo("answer_id", "Answers", "id", array("foreignKey" => true));
        }

}
