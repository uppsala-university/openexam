<?php

namespace OpenExam\Models;

class Topic extends ModelBase
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
         *
         * @var string
         */
        public $grades;
        /**
         *
         * @var string
         */
        public $depend;

        /**
         * Initialize method for model.
         */
        public function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Questions', 'topic_id', array('alias' => 'Questions'));
        }

        public function getSource()
        {
                return 'topics';
        }

}
