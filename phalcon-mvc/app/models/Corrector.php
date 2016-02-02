<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Corrector.php
// Created: 2014-09-17 05:33:46
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

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

                $this->hasMany("id", "OpenExam\Models\Result", "corrector_id", array(
                        'alias'    => 'results',
                        'reusable' => true
                ));
                $this->belongsTo('question_id', 'OpenExam\Models\Question', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'question',
                        'reusable'   => true
                ));
        }

        public function getSource()
        {
                return 'correctors';
        }

}
