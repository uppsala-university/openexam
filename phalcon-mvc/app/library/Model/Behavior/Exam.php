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

use OpenExam\Library\Model\Exception;
use OpenExam\Models\Contributor;
use OpenExam\Models\Decoder;
use OpenExam\Models\Exam as ExamModel;
use OpenExam\Models\Invigilator;
use OpenExam\Models\Topic;
use Phalcon\Mvc\ModelInterface;

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
         * @param ExamModel $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                // 
                // Delegate contributor, invigilator and decoder roles:
                // 
                if ($type == 'afterCreate') {
                        $this->trustedContextCall(function() use($model) {
                                $contributor = new Contributor();
                                $contributor->user = $model->creator;
                                $contributor->exam_id = $model->id;
                                if ($contributor->save() == false) {
                                        throw new Exception("Failed add contributor by behavior (" . $contributor->getMessages()[0] . ")");
                                }

                                $invigilator = new Invigilator();
                                $invigilator->user = $model->creator;
                                $invigilator->exam_id = $model->id;
                                if ($invigilator->save() == false) {
                                        throw new Exception("Failed add invigilator by behavior (" . $invigilator->getMessages()[0] . ")");
                                }

                                $decoder = new Decoder();
                                $decoder->user = $model->creator;
                                $decoder->exam_id = $model->id;
                                if ($decoder->save() == false) {
                                        throw new Exception("Failed add decoder by behavior (" . $decoder->getMessages()[0] . ")");
                                }

                                $topic = new Topic();
                                $topic->name = 'default';
                                $topic->exam_id = $model->id;
                                if ($topic->save() == false) {
                                        throw new Exception("Failed add default topic by behavior (" . $topic->getMessages()[0] . ")");
                                }
                        }, $model->getDI());
                }
        }

}
