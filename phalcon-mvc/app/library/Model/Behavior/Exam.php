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
         * @param ExamModel $exam The target model.
         */
        public function notify($type, ModelInterface $exam)
        {
                // 
                // Delegate contributor, invigilator and decoder roles:
                // 
                if ($type == 'afterCreate') {
                        $this->trustedContextCall(function() use($exam) {
                                // 
                                // Add contributor role by behavior:
                                // 
                                $model = new Contributor();
                                if (($transaction = $exam->getTransaction())) {
                                        $model->setTransaction($transaction);
                                }

                                $model->user = $exam->creator;
                                $model->exam_id = $exam->id;
                                if ($model->save() == false) {
                                        throw new Exception("Failed add contributor by behavior (" . $model->getMessages()[0] . ")");
                                }

                                // 
                                // Add invigilator role by behavior:
                                // 
                                $model = new Invigilator();
                                if (($transaction = $exam->getTransaction())) {
                                        $model->setTransaction($transaction);
                                }

                                $model->user = $exam->creator;
                                $model->exam_id = $exam->id;
                                if ($model->save() == false) {
                                        throw new Exception("Failed add invigilator by behavior (" . $model->getMessages()[0] . ")");
                                }

                                // 
                                // Add decoder role by behavior:
                                // 
                                $model = new Decoder();
                                if (($transaction = $exam->getTransaction())) {
                                        $model->setTransaction($transaction);
                                }

                                $model->user = $exam->creator;
                                $model->exam_id = $exam->id;
                                if ($model->save() == false) {
                                        throw new Exception("Failed add decoder by behavior (" . $model->getMessages()[0] . ")");
                                }

                                // 
                                // Add default topic by behavior:
                                // 
                                $model = new Topic();
                                if (($transaction = $exam->getTransaction())) {
                                        $model->setTransaction($transaction);
                                }

                                $model->name = 'default';
                                $model->exam_id = $exam->id;
                                if ($model->save() == false) {
                                        throw new Exception("Failed add default topic by behavior (" . $model->getMessages()[0] . ")");
                                }
                        }, $exam->getDI());
                }
        }

}
