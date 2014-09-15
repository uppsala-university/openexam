<?php

namespace OpenExam\Models;

class Result extends ModelBase
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
                parent::initialize();
                $this->belongsTo('answer_id', 'OpenExam\Models\Answer', 'id', array('foreignKey' => true, 'alias' => 'Answer'));
        }

        public function getSource()
        {
                return 'results';
        }

}
