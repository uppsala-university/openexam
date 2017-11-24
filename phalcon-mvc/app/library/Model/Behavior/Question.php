<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Question.php
// Created: 2014-11-13 01:09:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior;

use OpenExam\Library\Model\Exception;
use OpenExam\Library\Security\Roles;
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
