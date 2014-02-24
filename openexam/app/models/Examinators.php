<?php

class Examinators extends \Phalcon\Mvc\Model
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
         * Initialize method for model.
         */
        public function initialize()
        {
                $this->belongsTo("exam_id", "Exams", "id", array("foreignKey" => true));
        }

}
