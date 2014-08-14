<?php

namespace OpenExam\Models;

class Topics extends ModelBase
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
        public $name;
        /**
         *
         * @var integer
         */
        public $randomize;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "Questions", "topic_id", NULL);
        }

        public function getSource()
        {
                return 'topics';
        }

}
