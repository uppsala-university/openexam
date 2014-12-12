<?php

namespace OpenExam\Models;

/**
 * The corrector model.
 * 
 * Represents a user having the corrector role on the related questiton.
 * @property Question $question The related question.
 * @property Result[] $results The related results.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Corrector extends Role
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

        protected function initialize()
        {
                parent::initialize();
                $this->hasMany("id", "OpenExam\Models\Result", "corrector_id", array("alias" => 'results'));
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array('foreignKey' => true, 'alias' => 'question'));
        }

        public function getSource()
        {
                return 'correctors';
        }

}
