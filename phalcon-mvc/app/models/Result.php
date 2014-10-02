<?php

namespace OpenExam\Models;

/**
 * The result model.
 * 
 * @property Answer $answer The related answer.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Result extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The answer ID.
         * @var integer
         */
        public $answer_id;
        /**
         * The answer score.
         * @var double
         */
        public $score;
        /**
         * Comment for answer.
         * @var string
         */
        public $comment;

        protected function initialize()
        {
                parent::initialize();
                $this->belongsTo('answer_id', 'OpenExam\Models\Answer', 'id', array('foreignKey' => true, 'alias' => 'answer'));
        }

        public function getSource()
        {
                return 'results';
        }

}
