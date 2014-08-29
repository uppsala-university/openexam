<?php

namespace OpenExam\Models;

class Decoder extends ModelBase
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
                parent::initialize();
                $this->belongsTo("exam_id", "Exam", "id", array("foreignKey" => true));
        }

        public function getSource()
        {
                return 'decoders';
        }

}
