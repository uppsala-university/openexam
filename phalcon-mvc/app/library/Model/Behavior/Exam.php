<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Exam.php
// Created: 2014-11-13 02:50:40
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior;

/**
 * Behavior for exam model.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Exam extends ModelBehavior
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param \OpenExam\Models\Exam $model The target model.
         */
        public function notify($type, $model)
        {
                // 
                // Delegate contributor and decoder roles:
                // 
                if ($type == 'afterCreate') {
                        $this->trustedContextCall(function($user) use($model) {
                                $role = new \OpenExam\Models\Contributor();
                                $role->user = $user->getPrincipalName();
                                $role->exam_id = $model->id;
                                $role->save();

                                $role = new \OpenExam\Models\Decoder();
                                $role->user = $user->getPrincipalName();
                                $role->exam_id = $model->id;
                                $role->save();
                                
                                $topic = new \OpenExam\Models\Topic();
                                $topic->name = 'default';
                                $topic->exam_id = $model->id;
                                $topic->save();
                        }, $model->getDI());
                }

                // 
                // Delete roles when exam is deleted:
                // 
                if ($type == 'beforeDelete') {
                        $this->trustedContextCall(function($user) use($model) {
                                $model->contributors->delete();
                                $model->decoders->delete();
                                $model->invigilators->delete();
                                $model->students->delete();
                        }, $model->getDI());
                }
        }

}
