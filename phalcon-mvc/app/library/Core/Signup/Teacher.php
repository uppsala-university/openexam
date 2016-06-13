<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Teacher.php
// Created: 2015-03-13 16:13:19
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Signup;

use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use OpenExam\Models\Teacher as TeacherModel;

/**
 * Teacher signup class.
 * 
 * This class provides extended functionality for adding a logged in user 
 * as a teacher in the system. It could be used from a signup page or as an 
 * automatic task applied when a user is logged on and detected to be an 
 * employee.
 * 
 * It's used for examine the signup information in the system configuration.
 * It handles two tasks:
 * 
 * <ul>
 * <li>Adding user as a teacher in the system.</li>
 * <li>Cloning and assigning any of the configured example examinations.</li>
 * </ul>
 * 
 * By default, all operations is done using the current logged on user. It
 * can be overridden by calling setUser().
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Teacher extends SignupBase
{

        /**
         * Constructor.
         * @param string $user The target user principal name.
         */
        public function __construct($user = null)
        {
                parent::__construct($user);

                if ($this->config->get('signup') == false) {
                        $this->_enabled = false;
                } elseif ($this->config->signup->get('teacher') == false) {
                        $this->_enabled = false;
                } else {
                        $this->_enabled = true;
                        $this->_exams = $this->config->signup->teacher->toArray();
                }
        }

        /**
         * Insert current selected user as teacher in the system.
         * 
         * Return true if caller was inserted and false if role has already
         * been granted. Throws exception on failure.
         * 
         * @return boolean
         * @throws ModelException
         */
        public function insert()
        {
                $role = $this->user->setPrimaryRole(Roles::SYSTEM);

                try {
                        if (($found = TeacherModel::count(array(
                                    "user = :user:",
                                    "bind" => array('user' => $this->_caller)
                            ))) == 0) {
                                $teacher = new TeacherModel();
                                $teacher->user = $this->_caller;
                                if ($teacher->save() == false) {
                                        throw new ModelException($teacher->getMessages()[0]);
                                }
                        }
                } catch (ModelException $exception) {
                        $this->user->setPrimaryRole($role);
                        throw $exception;
                }

                $this->user->setPrimaryRole($role);
                return $found == 0;
        }

        /**
         * Remove current selected user as teacher in the system.
         * 
         * Return true if caller was removed and false if role don't exist
         * for caller. Throws exception on failure.
         * 
         * @return boolean
         * @throws ModelException
         */
        public function remove()
        {
                $role = $this->user->setPrimaryRole(Roles::SYSTEM);

                try {
                        if (($teacher = TeacherModel::findFirst(array(
                                    "user = :user:",
                                    "bind" => array('user' => $this->_caller)
                            ))) != false) {
                                if ($teacher->delete() == false) {
                                        throw new ModelException($teacher->getMessages()[0]);
                                }
                        }
                } catch (ModelException $exception) {
                        $this->user->setPrimaryRole($role);
                        throw $exception;
                }
                
                $this->user->setPrimaryRole($role);
                return $teacher != false;
        }

        /**
         * Clone and assign exam.
         * 
         * The $index is the exam to clone and assign. The $user is the user
         * principal name of the user being assigned as creator of the cloned
         * exam.
         * 
         * @param int $index The exam ID.
         */
        public function assign($index)
        {
                if (!in_array($index, $this->_exams)) {
                        throw new SecurityException(
                        "Exam $index is not in list of available exams.", SecurityException::ACTION
                        );
                }

                $role = $this->user->setPrimaryRole(Roles::SYSTEM);
                try {
                        // 
                        // Find exam to clone:
                        // 
                        if (!($curr = Exam::findFirstById($index))) {
                                throw new ModelException("Failed find exam $index");
                        } else {
                                $this->duplicate($curr);
                        }
                } catch (ModelException $exception) {
                        $this->user->setPrimaryRole($role);
                        throw $exception;
                }

                $this->user->setPrimaryRole($role);
                return true;
        }

        /**
         * Duplicate an existing exam.
         * 
         * @param Exam $curr The original exam.
         * @throws ModelException
         */
        private function duplicate($curr)
        {
                $exam = new Exam();
                $exam->name = $curr->name;
                $exam->descr = $curr->descr;
                $exam->creator = $this->_caller;
                $exam->details = $curr->details;
                $exam->orgunit = $curr->orgunit;
                $exam->grades = $curr->grades;
                $exam->code = "SIGNUP";

                if ($exam->create() == false) {
                        throw new ModelException($exam->getMessages()[0]);
                }
                if ($exam->topics->delete() == false) {
                        throw new ModelException($exam->getMessages()[0]);
                }

                foreach ($curr->topics as $topic) {
                        $questions = $topic->questions;
                        $topic->id = null;
                        $topic->exam_id = $exam->id;
                        if ($topic->create() == false) {
                                throw new ModelException($topic->getMessages()[0]);
                        }
                        foreach ($questions as $question) {
                                $question->id = null;
                                $question->exam_id = $exam->id;
                                $question->topic_id = $topic->id;
                                $question->user = $this->_caller;
                                if ($question->create() == false) {
                                        throw new ModelException($question->getMessages()[0]);
                                }
                        }
                }

                foreach ($curr->resources as $resource) {
                        $resource->id = null;
                        $resource->exam_id = $exam->id;
                        $resource->user = $this->_caller;
                        if ($resource->create() == false) {
                                throw new ModelException($question->getMessages()[0]);
                        }
                }

                if ($exam->save() == false) {
                        throw new ModelException($exam->getMessages()[0]);
                }
        }

        /**
         * Check if signup has been applied.
         * 
         * Return true if at least one signup actions has been applied. The
         * check is done by counting the number of exams having the caller 
         * as creator and with SIGNUP as exam code.
         * 
         * @return boolean 
         */
        public function isApplied()
        {
                return Exam::count(array(
                            "creator = :user: AND code = 'SIGNUP'",
                            "bind" => array(
                                    "user" => $this->_caller
                            )
                    )) > 0;
        }
        
}
