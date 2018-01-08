<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Result.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\FilterText;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Answer as AnswerModelGuard;
use OpenExam\Library\Model\Guard\Corrector as CorrectorModelGuard;
use OpenExam\Library\Model\Validation\InvalidFormat;
use Phalcon\Mvc\Model\Validator\Uniqueness;
use Phalcon\Validation;
use Phalcon\Validation\Validator\InclusionIn;

/**
 * The result model.
 * 
 * @property Answer $answer The related answer.
 * @property Question $question The related question.
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
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'question'
                ));
                $this->belongsTo('corrector_id', 'OpenExam\Models\Corrector', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'corrector',
                        'reusable'   => true
                ));

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('score', 'comment'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('score', 'comment'),
                                'value' => null
                        )
                )));

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
                        'question_id'  => 'question_id',
                        'correction'   => 'correction',
                        'score'        => 'score',
                        'comment'      => 'comment'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        public function validation()
        {
                $validator = new Validation();

                $validator->add(
                    'correction', new InclusionIn(
                    array(
                        'domain' => array(
                                self::CORRECTION_WAITING,
                                self::CORRECTION_PARTIAL,
                                self::CORRECTION_COMPLETED,
                                self::CORRECTION_FINALIZED
                        )
                    )
                ));
                $validator->add(
                    'score', new InvalidFormat(
                    array(
                        'input' => '{}'
                    )
                ));
                $validator->add(
                    'answer_id', new Uniqueness(
                    array(
                        'message' => "The answer $this->answer_id has already an result."
                    )
                ));

                return $this->validate($validator);
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
