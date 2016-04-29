<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Access.php
// Created: 2014-12-16 11:07:43
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Student;

use Exception;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use OpenExam\Models\Lock;
use OpenExam\Models\Student;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Mvc\User\Component;

/**
 * Common exam handling.
 * 
 * The open() method should be called when accessing an exam as student 
 * during examination. It will perform the required actions (house keeping) 
 * and also enforce access restriction and lockdown.
 * 
 * Call the close() method when done. It will perform any cleanup. Once
 * called, the exam is no longer accessable for answering.
 * 
 * The approve() and release() are staff functions. Use approve() for granting 
 * access to an exam, using the with student/computer associated lock. Use
 * release() to remove a student/computer associated lock, thus allowing the
 * exam for being opened from another computer.
 * 
 * Usage:
 * <code>
 * $access = new Access(Exam::findFirstById($this->request->getParam('exam'));
 * 
 * // Called on behalf of the student role:
 * $access->open();     // In response to access exam from controller.
 * $access->close();    // In response to closing (finishing) the exam.
 * 
 * // Called on behalf of e.g. the invigilator role:
 * $access->approve(Lock::findFirstById($this->request->getParam('lock'));
 * $access->release(Lock::findFirstById($this->request->getParam('lock'));
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Access extends Component
{

        /**
         * Open exam approved.
         */
        const OPEN_APPROVED = 1;
        /**
         * Open exam pending.
         */
        const OPEN_PENDING = 2;
        /**
         * Open exam denied.
         */
        const OPEN_DENIED = 3;

        /**
         * The current exam.
         * @var Exam 
         */
        private $_exam;

        /**
         * Constructor.
         * @param Exam $exam The current exam.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
        }

        /**
         * Open exam as a student.
         * 
         * This function should be called as a student whenever trying to
         * access an ongoing exam. It will do any required house keeping
         * and enforce access control.
         * 
         * Return OPEN_APPROVED if access is permitted or OPEN_PENDING if
         * access is waiting for approval. Returns OPEN_DENIED or throws an
         * exception if access is not permitted.
         * 
         * @return int One of the OPEN_XXX constants.
         * @throws Exception
         */
        public function open()
        {
                // 
                // Check that caller is student. Once verified, continue the
                // process using the system role.
                // 
                if (($this->user->getPrimaryRole() != Roles::STUDENT)) {
                        throw new SecurityException("Opening the exam requires the student role.", SecurityException::ROLE);
                } else {
                        $role = $this->user->setPrimaryRole(Roles::SYSTEM);
                }

                try {
                        // 
                        // Get student object.
                        // 
                        if (($student = Student::findFirst(array(
                                    'conditions' => 'exam_id = :exam: AND user = :user:',
                                    'bind'       => array(
                                            'exam' => $this->_exam->id,
                                            'user' => $this->user->getPrincipalName()
                                    )
                            ))) == false) {
                                throw new SecurityException("You are not subscribed on this exam", SecurityException::ACCESS);
                        }

                        // 
                        // Check student access:
                        // 
                        $lockdown = new Lockdown($this->_exam, $student);
                        if (($status = $lockdown->accepted()) != self::OPEN_APPROVED) {
                                return $status;
                        }

                        // 
                        // Prepare exam for first use if needed:
                        // 
                        $setup = new Setup($this->_exam, $student);
                        $setup->prepare();
                } catch (Exception $exception) {
                        $this->user->setPrimaryRole($role);
                        throw $exception;
                }

                $this->user->setPrimaryRole($role);
                return self::OPEN_APPROVED;
        }

        /**
         * Close exam as a student.
         * 
         * This function should be called as a student for closing the exam.
         * Once closed, the exam can no longer be opened unless the invigilator
         * opens it again by calling release().
         * 
         * @return boolean
         * @throws Exception
         */
        public function close()
        {
                if (($this->user->getPrimaryRole() != Roles::STUDENT)) {
                        throw new SecurityException("Closing the exam requires the student role.", SecurityException::ROLE);
                } else {
                        $role = $this->user->setPrimaryRole(Roles::SYSTEM);
                }

                try {
                        // 
                        // Get student object.
                        // 
                        if (($student = Student::findFirst(array(
                                    'conditions' => 'exam_id = :exam: AND user = :user:',
                                    'bind'       => array(
                                            'exam' => $this->_exam->id,
                                            'user' => $this->user->getPrincipalName()
                                    )
                            ))) == false) {
                                throw new SecurityException("You are not subscribed on this exam", SecurityException::ACCESS);
                        }
                        // 
                        // Get computer object.
                        // 
                        if (($computer = Computer::findFirst(array(
                                    'conditions' => 'ipaddr = :ipaddr:',
                                    'bind'       => array(
                                            'ipaddr' => $this->request->getClientAddress(true)
                                    )
                            ))) == false) {
                                throw new SecurityException("Failed lookup client computer", SecurityException::ACCESS);
                        }
                        // 
                        // Get exam lock:
                        // 
                        if (($lock = Lock::findFirst(array(
                                    'conditions' => 'student_id = :stud: computer_id = :comp: AND exam_id = :exam:',
                                    'bind'       => array(
                                            'stud' => $student->id,
                                            'comp' => $computer->id,
                                            'exam' => $this->_exam->id
                                    )
                            ))) == false) {
                                throw new SecurityException("Failed lookup exam lock", SecurityException::ACCESS);
                        }

                        // 
                        // Set student exam as finished:
                        // 
                        $student->finished = strftime('%x %X');
                        if ($student->update() == false) {
                                throw new ModelException($student->getMessages()[0]);
                        }

                        // 
                        // Remove exam lock. Once flagged as finished, the 
                        // exam will be locked anyway.
                        // 
                        if ($lock->delete() == false) {
                                throw new ModelException($student->getMessages()[0]);
                        }
                } catch (Exception $exception) {
                        $this->user->setPrimaryRole($role);
                        throw $exception;
                }

                $this->user->setPrimaryRole($role);
                return true;
        }

        /**
         * Release exam lock.
         * 
         * This method removes the exam lock and allowes the exam to be opened
         * from another computer. Calling this method requires the caller to
         * have a primary role with permissions to release exam locks, typical
         * the invigilator role.
         * 
         * @param Lock $lock The exam lock to remove.
         * @throws Exception
         */
        public function release(Lock $lock)
        {
                if ($this->capabilities->hasCapability($lock, ObjectAccess::DELETE) == false) {
                        throw new SecurityException("You don't have permission to release this exam lock.", SecurityException::ROLE);
                }

                if ($lock->delete() == false) {
                        throw new ModelException($lock->getMessages()[0]);
                }
        }

        /**
         * Approve pending exam lock.
         * 
         * @param Lock $lock The exam lock to approve.
         * @throws Exception
         */
        public function approve(Lock $lock)
        {
                if ($this->capabilities->hasCapability($lock, ObjectAccess::UPDATE) == false) {
                        throw new SecurityException("You don't have permission to approve this exam lock.", SecurityException::ROLE);
                }

                $lock->status = Lock::STATUS_APPROVED;
                if ($lock->update() == false) {
                        throw new ModelException($lock->getMessages()[0]);
                }
        }

}
