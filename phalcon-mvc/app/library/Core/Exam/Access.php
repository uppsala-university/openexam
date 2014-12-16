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

namespace OpenExam\Library\Core\Exam;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use OpenExam\Models\Lock;
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
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Access extends Component
{

        /**
         * The current exam.
         * @var Exam 
         */
        private $exam;

        /**
         * Constructor.
         * @param Exam $exam The current exam.
         */
        public function __construct($exam)
        {
                $this->exam = $exam;
        }

        /**
         * Open exam as a student.
         * 
         * This function should be called as a student whenever trying to
         * access an ongoing exam. It will do any required house keeping
         * and enforce access control.
         * 
         * @return boolean
         * @throws Exception
         */
        public function open()
        {
                if (($this->user->getPrimaryRole() != Roles::STUDENT)) {
                        throw new Exception($this->tr->_("Opening the exam should be done as a student."), Exception::ROLE);
                }
                return true;
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
                        throw new Exception($this->tr->_("Closing the exam should be done as a student."), Exception::ROLE);
                }
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
         * @return boolean
         * @throws Exception
         */
        public function release($lock)
        {
                if ($this->capabilities->hasCapability($lock, ObjectAccess::DELETE) == false) {
                        throw new Exception($this->tr->_("You don't have permission to release the exam lock."), Exception::ROLE);
                }
                return true;
        }

        /**
         * Approve pending exam lock.
         * 
         * @param Lock $lock The exam lock to approve.
         * @return boolean
         * @throws Exception
         */
        public function approve($lock)
        {
                if ($this->capabilities->hasCapability($lock, ObjectAccess::UPDATE) == false) {
                        throw new Exception($this->tr->_("You don't have permission to approve the exam lock."), Exception::ROLE);
                }
                return true;
        }

}
