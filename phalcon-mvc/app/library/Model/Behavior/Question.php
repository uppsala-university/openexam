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
         * @param \OpenExam\Models\Question $model The target model.
         */
        public function notify($type, $model)
        {
                // 
                // Add caller as question corrector:
                // 
                if ($type == 'afterCreate') {
                        $this->trustedContextCall(function($user) use($model) {
                                $corrector = new \OpenExam\Models\Corrector();
                                $corrector->user = $user->getPrincipalName();
                                $corrector->question_id = $model->id;
                                return $corrector->save();
                        }, $model->getDI());
                }

                // 
                // Delete correctors when question is deleted:
                // 
                if ($type == 'beforeDelete') {
                        $this->trustedContextCall(function($user) use($model) {
                                return $model->correctors->delete();
                        }, $model->getDI());
                }
        }

}
