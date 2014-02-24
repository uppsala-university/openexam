<?php

class Locks extends \Phalcon\Mvc\Model
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
        public $computer_id;
        /**
         *
         * @var integer
         */
        public $exam_id;
        /**
         *
         * @var string
         */
        public $aquired;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                $this->belongsTo("computer_id", "Computers", "id", array("foreignKey" => true));
                $this->belongsTo("exam_id", "Exams", "id", array("foreignKey" => true));
        }

}
