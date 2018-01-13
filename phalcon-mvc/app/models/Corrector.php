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
// File:    Corrector.php
// Created: 2014-09-17 05:33:46
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Guard\Question as QuestionModelGuard;

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
         * Guard against problematic methods use.
         */
        use QuestionModelGuard;

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

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'correctors';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'          => 'id',
                        'question_id' => 'question_id',
                        'user'        => 'user'
                );
        }

        /**
         * Called after model is created.
         */
        public function afterCreate()
        {
                parent::afterCreate();
                $this->question->exam->getStaff()->addRole($this);
        }

        /**
         * Called after model is deleted.
         */
        public function afterDelete()
        {
                parent::afterDelete();
                $this->question->exam->getStaff()->removeRole($this);
        }

}
