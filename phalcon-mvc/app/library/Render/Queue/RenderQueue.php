<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    RenderQueue.php
// Created: 2017-12-07 01:04:11
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Render\Queue;

use OpenExam\Library\Render\Exception as RenderException;
use OpenExam\Models\Render;
use OpenExam\Models\Student;
use Phalcon\Mvc\User\Component;

/**
 * The render queue class.
 * 
 * Acts as a layer between the caller and the render queue model. In its role
 * as a producer it's responsible for configure render URL (if required) and
 * preparing disk storage before saving the render model.
 *
 * @author Anders Lövgren (QNET)
 */
class RenderQueue extends Component
{

        /**
         * Constructor.
         * @throws RenderException
         */
        public function __construct()
        {
                if (!isset($this->config->result->public)) {
                        $this->config->result->public = false;
                }
        }

        /**
         * Find render job.
         * 
         * This method tries to find an existing render job. If missing, then
         * an new model is returned with status == missing and populated with
         * method call parameters.
         * 
         * @param int $eid The exam ID.
         * @param string $type The render type (one of the TYPE_XXX constants).
         * @param string $user The render result owner.
         * @return Render The render model.
         */
        public function findJob($eid, $type, $user)
        {
                if (!($job = Render::findFirst(array(
                            'conditions' => 'exam_id = :exam: AND type = :type: AND user = :user:',
                            'bind'       => array(
                                    'exam' => $eid,
                                    'type' => $type,
                                    'user' => $user
                            )
                    )))) {
                        $model = new Render();
                        $model->assign(array(
                                'exam_id' => $eid,
                                'type'    => $type,
                                'user'    => $user,
                                'status'  => Render::STATUS_MISSING
                        ));
                        return $model;
                } else {
                        return $job;
                }
        }

        /**
         * Add render job.
         * 
         * The job gets inserted in the render queue as queued and left for either
         * one of the render workers to pick up. Returns the render job ID (from the
         * render model) to caller. Caller can use the job ID to poll for job status 
         * and fetch the result once finished.
         * 
         * The actual data to pass depends on render type. For result the extra parameter 
         * student must be passed. For extern type only the URL property is required. 
         * Use one of the TYPE_XXX constants. If user is not set, then it will default 
         * to caller.
         * 
         * @param Render $render The render model.
         * @param Student $student Optional student model.
         */
        public function addJob($render, $student = null)
        {
                $target = new RenderTarget($render, $student);
                $target->setPath();

                $source = new RenderSource($render, $student);
                $source->setUrl();

                $render->queued = strftime("%Y-%m-%d %T");
                $render->status = Render::STATUS_QUEUED;

                if (!isset($render->user)) {
                        $render->user = $this->user->getPrincipalName();
                }

                if (!$render->save()) {
                        throw new RenderException($render->getMessages()[0]);
                }

                return $render;
        }

        /**
         * Cancel render job.
         * 
         * Pass job ID to this method to cancel the render job. Caller must be 
         * the job owner for this method to succeed.
         * 
         * @param int $jid The job ID.
         * @return boolean
         */
        public function cancelJob($jid)
        {
                if (!($job = Render::findFirst($jid))) {
                        return false;
                }
                if (($job->user != $this->user->getPrincipalName())) {
                        return false;
                }
                if ($job->status != Render::STATUS_RENDER) {
                        $job->delete();
                        return true;
                }
        }

        /**
         * Get render job status.
         * 
         * Pass job ID to get status of the render job. Return the render model
         * as all properties are useful for caller.
         * 
         * @param int $jid The job ID.
         * @return Render
         */
        public function getStatus($jid)
        {
                if (!($job = Render::findFirst($jid))) {
                        return false;
                } else {
                        return $job;
                }
        }

        /**
         * Get result path.
         * 
         * Returns the path for the rendered file or false if job is missing.
         * 
         * @param int $jid The job ID.
         * @return string
         */
        public function getResult($jid)
        {
                if (!($job = Render::findFirst($jid))) {
                        return false;
                } else {
                        return $job->path;
                }
        }

        /**
         * Get position in the render queue.
         * 
         * @param int $jid The job ID.
         * @return int
         */
        public function getPosition($jid)
        {
                return Render::count(array(
                            "conditions" => "id <= :job: AND status = :status:",
                            "bind"       => array(
                                    "job"    => $jid,
                                    "status" => "queued"
                            )
                ));
        }

        /**
         * Get student model.
         * 
         * @param string $user The principal name.
         * @param int $eid The exam ID.
         * @return Student
         * @throws RenderException
         */
        public static function getStudent($user, $eid)
        {
                if (!($student = Student::findFirst(array(
                            "conditions" => "exam_id = :exam: AND user = :user:",
                            "bind"       => array(
                                    "user" => $user,
                                    "exam" => $eid
                            ))
                    ))) {
                        throw new RenderException("Failed lookup student from $user");
                } else {
                        return $student;
                }
        }

        /**
         * Update render job.
         * 
         * @param int $eid The exam ID.
         * @param string $type The render type (one of the TYPE_XXX constants).
         * @param string $user The render result owner.
         * @return Render The render model.
         */
        public function updateJob($eid, $type, $user)
        {
                // 
                // Get new or existing job:
                // 
                $model = $this->findJob($eid, $type, $user);
                // $model->id = null;
                // 
                // Result requires the student model:
                // 
                if ($model->type == Render::TYPE_RESULT) {
                        $student = RenderQueue::getStudent($user, $eid);
                } else {
                        $student = null;
                }

                // 
                // Delete result file if exist:
                // 
                $this->deleteResult($model->path);

                // 
                // Push render job on queue:
                // 
                return $this->addJob($model, $student);
        }

        /**
         * Delete result file.
         * 
         * @param string $path The path relative to cache directory.
         * @throws RenderException
         */
        private function deleteResult($path)
        {
                if (!isset($path)) {
                        return;
                } else {
                        $filename = sprintf("%s/%s", $this->config->application->cacheDir, $path);
                }

                if (!file_exists($filename)) {
                        return;
                }
                if (!unlink($filename)) {
                        throw new RenderException("Failed unlink result file");
                }
        }

        /**
         * Check if student can access render queue.
         * 
         * The check is performed on this exam and checks if student or exam
         * has enquiry flag enabled (nuke if under investigation). The second
         * check is global and check if student has active exam locks (an exam
         * not yet finished).
         * 
         * @param Render $render The render model.
         * @throws RenderException
         */
        public function canAccess($render)
        {
                // 
                // Get all student models for calling user:
                // 
                if (!($students = Student::find(array(
                            'conditions' => 'user = :user:',
                            'bind'       => array(
                                    'user' => $this->user->getPrincipalName()
                            )
                    )))) {
                        throw new RenderException("Failed find students");
                }

                // 
                // Loop thru all student models and check for possible causes
                // to nuke access:
                // 
                foreach ($students as $student) {
                        if ($student->exam_id == $render->exam_id && $student->exam->enquiry) {
                                throw new RenderException("Access to result is denied because the enquiry flag has been set on this exam.");
                        }
                        if ($student->exam_id == $render->exam_id && $student->enquiry) {
                                throw new RenderException("Access to result is denied because the enquiry flag has been set for you on this exam.");
                        }
                        if (!isset($student->exam->endtime) || !isset($student->locks->acquired)) {
                                continue;
                        }
                        if (strtotime($student->exam->endtime) > strtotime($student->locks->acquired)) {
                                throw new RenderException("Access to render queue is not allowed while having an ongoing exam");
                        }
                }

                // 
                // Check if result access should be restricted:
                // 
                if ($this->config->result->public == false) {
                        if ($render->type == Render::TYPE_RESULT &&
                            $render->user != $this->user->getPrincipalName()) {
                                throw new RenderException("By public result restrictions you are not allowed to access others result");
                        }
                }
        }

}
