<?php

namespace OpenExam\Models;

/**
 * The corrector model.
 * 
 * Represents a user having the corrector role on the related questiton.
 * @property Question $question The related question.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Corrector extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The question ID.
         * @var integer
         */
        public $question_id;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;

        public function initialize()
        {
                parent::initialize();
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array('foreignKey' => true, 'alias' => 'Question'));
        }

        public function getSource()
        {
                return 'correctors';
        }

}
