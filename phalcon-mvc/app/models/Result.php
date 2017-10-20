<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Result.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\FilterText;
use OpenExam\Library\Model\Guard\Answer as AnswerModelGuard;
use OpenExam\Library\Model\Guard\Corrector as CorrectorModelGuard;
use Phalcon\Mvc\Model\Validator\Inclusionin;
use Phalcon\Mvc\Model\Validator\Uniqueness;

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
         * Guard against problematic methods use.
         */
        use AnswerModelGuard;
        use CorrectorModelGuard;

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
                        'alias'      => 'corrector',
                        'reusable'   => true
                ));

                // 
                // TODO: better do filtering on client side.
                // 
                $this->addBehavior(new FilterText(array(
                        'beforeValidationOnCreate' => array(
                                'fields' => 'comment'
                        ),
                        'beforeValidationOnUpdate' => array(
                                'fields' => 'comment'
                        )
                    )
                ));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'results';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'           => 'id',
                        'answer_id'    => 'answer_id',
                        'corrector_id' => 'corrector_id',
                        'correction'   => 'correction',
                        'score'        => 'score',
                        'comment'      => 'comment'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(
                    array(
                        'field'  => 'correction',
                        'domain' => array(self::CORRECTION_WAITING, self::CORRECTION_PARTIAL, self::CORRECTION_COMPLETED, self::CORRECTION_FINALIZED)
                    )
                ));
                $this->validate(new Uniqueness(
                    array(
                        'field'   => 'answer_id',
                        'message' => "The answer $this->answer_id has already an result."
                    )
                ));

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

}
