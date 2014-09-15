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
                $this->belongsTo('computer_id', 'OpenExam\Models\Computer', 'id', array('foreignKey' => true, 'alias' => 'Computer'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
        }

        public function beforeCreate()
        {
                $this->aquired = date('Y-m-d H:i:s');
        }

        public function getSource()
        {
                return 'locks';
        }

}
