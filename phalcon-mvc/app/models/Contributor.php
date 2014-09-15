<?php

namespace OpenExam\Models;

class Contributor extends ModelBase
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
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
        }

        public function getSource()
        {
                return 'contributors';
        }

}
