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
                                $contributor = new \OpenExam\Models\Contributor();
                                $contributor->user = $model->creator;
                                $contributor->exam_id = $model->id;
                                if ($contributor->save() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed add contributor by behavior (" . $contributor->getMessages()[0] . ")");
                                }

                                $decoder = new \OpenExam\Models\Decoder();
                                $decoder->user = $model->creator;
                                $decoder->exam_id = $model->id;
                                if ($decoder->save() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed add decoder by behavior (" . $decoder->getMessages()[0] . ")");
                                }

                                $topic = new \OpenExam\Models\Topic();
                                $topic->name = 'default';
                                $topic->exam_id = $model->id;
                                if ($topic->save() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed add default topic by behavior (" . $topic->getMessages()[0] . ")");
                                }
                        }, $model->getDI());
                }

                // 
                // Delete roles when exam is deleted:
                // 
                if ($type == 'beforeDelete') {
                        $this->trustedContextCall(function($user) use($model) {
                                if ($model->students->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete students by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->contributors->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete contributors by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->decoders->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete decoders by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->invigilators->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete invigilators by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->resources->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete resources by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->questions->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete questions by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->topics->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete topics by behavior (" . $model->getMessages()[0] . ")");
                                }
                                if ($model->locks->delete() == false) {
                                        throw new \OpenExam\Library\Model\Exception("Failed delete exam locks by behavior (" . $model->getMessages()[0] . ")");
                                }
                        }, $model->getDI());
                }
        }

}
