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
// File:    Question.php
// Created: 2014-11-13 01:09:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior;

use OpenExam\Library\Model\Exception;
use OpenExam\Models\Corrector;
use OpenExam\Models\Question as QuestionModel;
use Phalcon\Mvc\ModelInterface;

/**
 * Behavior for question model.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Question extends ModelBehavior
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param QuestionModel $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                if ($type == 'afterCreate') {
                        $this->trustedContextCall(function($user, $role) use($model) {
                                // 
                                // Add caller as question corrector:
                                // 
                                $corrector = new Corrector();
                                $corrector->user = $model->user;
                                $corrector->question_id = $model->id;
                                if ($corrector->save() == false) {
                                        throw new Exception("Failed add corrector by behavior (" . $corrector->getMessages()[0] . ")");
                                }
                        }, $model->getDI());
                }
        }

}
