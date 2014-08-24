<?php

namespace OpenExam\Models;

class Lock extends ModelBase
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
                parent::initialize();
                $this->belongsTo("computer_id", "Computer", "id", array("foreignKey" => true));
                $this->belongsTo("exam_id", "Exam", "id", array("foreignKey" => true));
        }

        public function getSource()
        {
                return 'locks';
        }

}
