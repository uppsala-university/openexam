<?php

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Validator\Inclusionin;

/**
 * The result model.
 * 
 * @property Answer $answer The related answer.
 * @property Corrector $corrector The related corrector.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Result extends ModelBase
{

        /**
         * Correction of answer is waiting to be done.
         */
        const CORRECTION_WAITING = 'waiting';
        /**
         * Correction of answer is partially completed.
         */
        const CORRECTION_PARTIAL = 'partial';
        /**
         * Correction of answer has been completed.
         */
        const CORRECTION_COMPLETED = 'completed';
        /**
         * Correction of answer has been locked as finalized.
         */
        const CORRECTION_FINALIZED = 'finalized';

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
         * The corrector ID.
         * @var integer 
         */
        public $corrector_id;
        /**
         * The correction state.
         * @var string 
         */
        public $correction;
        /**
         * Associative array of answer scores.
         * @var float[]
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

                $this->belongsTo('answer_id', 'OpenExam\Models\Answer', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'answer'
                ));
                $this->belongsTo('corrector_id', 'OpenExam\Models\Corrector', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'corrector'
                ));
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(array(
                        'field'  => 'correction',
                        'domain' => array(self::CORRECTION_WAITING, self::CORRECTION_PARTIAL, self::CORRECTION_COMPLETED, self::CORRECTION_FINALIZED)
                )));
                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->correction)) {
                        $this->correction = self::CORRECTION_WAITING;
                }
        }

        public function getSource()
        {
                return 'results';
        }

}
