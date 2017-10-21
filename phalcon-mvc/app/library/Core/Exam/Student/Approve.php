<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Approve.php
// Created: 2017-03-21 17:49:09
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Student;

use OpenExam\Library\Core\Exam\Access;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Models\Computer;
use OpenExam\Models\Exam;
use OpenExam\Models\Lock;
use OpenExam\Models\Student;
use Phalcon\Mvc\User\Component;

/**
 * Approve student access on exam.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Approve extends Component
{

        /**
         * @var Lock 
         */
        private $_lock;
        /**
         * @var Exam 
         */
        private $_exam;
        /**
         * @var Student 
         */
        private $_student;
        /**
         * The remote IP-address.
         * @var string 
         */
        private $_addr;

        /**
         * Constructor.
         * @param Exam $exam The current exam.
         * @param Student $student The current student.
         */
        public function __construct($exam, $student)
        {
                $this->_lock = false;
                $this->_exam = $exam;
                $this->_student = $student;
                $this->_addr = $this->request->getClientAddress(true);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_exam);
                unset($this->_lock);
                unset($this->_addr);
                unset($this->_student);
        }

        /**
         * Verify exam not finished.
         * 
         * The exam should only be accessable for students during the exam 
         * start and end time. Once the exam has been closed by the student,
         * it should no longer be possible to open it again.
         * 
         * Returns true if successful, otherwise false.
         * 
         * @return boolean
         * @throws SecurityException
         */
        public function isFinished()
        {
                if ($this->_exam->getState()->has(State::RUNNING) == false) {
                        throw new SecurityException("This exam is not open for access", SecurityException::ACCESS);
                }
                if (isset($this->_student->finished)) {
                        throw new SecurityException("Exam has been finished (closed by student) and can't be reopened", SecurityException::ACCESS);
                }

                return false;
        }

        /**
         * Check if approved exam lock exist for remote host.
         * 
         * Return true if lock has been approved and matches remote address,
         * otherwise return false.
         * 
         * @return boolean 
         * @throws SecurityException
         */
        public function isLocked()
        {
                // 
                // Bypass if lockdown is not enabled:
                // 
                if ($this->_exam->lockdown->enable == false) {
                        return true;
                }

                // 
                // Get existing lock:
                // 
                if (($this->_lock = Lock::findFirst(array(
                            'conditions' => 'exam_id = ?0 AND student_id = ?1',
                            'bind'       => array($this->_exam->id, $this->_student->id)
                    ))) === false) {
                        return false;
                }

                // 
                // Check if lock has been approved. If so, verify that it's
                // for remote computer.
                // 
                if ($this->_lock->status != Lock::STATUS_APPROVED) {
                        $this->logger->access->debug(sprintf("Continue open exam check for %s from %s (lock not approved: %s)", $this->_student->user, $this->_addr, $this->_lock->status));
                        return false;
                } elseif ($this->_lock->computer->ipaddr != $this->_addr) {
                        throw new SecurityException(sprintf("This exam is already locked to %s (%s) in %s, %s", $this->_lock->computer->hostname, $this->_lock->computer->ipaddr, $this->_lock->computer->room->name, $this->_lock->computer->room->description), SecurityException::ACCESS);
                }

                // 
                // Lock has been verified.
                // 
                return true;
        }

        /**
         * Verify IP-address or hostname of peer.
         * 
         * This function verifies that remote IP-address or hostname are allowed 
         * by the exam access list. Return true if permitted, otherwise false if
         * the connection is pending approval.
         * 
         * @return boolean
         * @throws ModelException
         */
        public function isAllowed()
        {
                // 
                // Use access list validator to simplify checking:
                // 
                $access = new Access($this->_exam);

                // 
                // Warn about empty access list. Users should be encourage to
                // explicit define access list.
                // 
                if ($access->isMissing()) {
                        $this->logger->access->warning(sprintf("The exam %d has no remote address access list", $this->_exam->id));
                        return true;
                }

                // 
                // Check if access is allowed using any access check:
                // 
                if ($access->isAllowed($this->_addr)) {
                        return true;
                }

                // 
                // Neither hostname or IP-address matched:
                // 
                return false;
        }

        /**
         * Check and apply client side lock.
         * 
         * Returns true if successful, otherwise false.
         * 
         * @param Computer $computer The computer model.
         * @return boolean
         */
        public function hasSecureClient($computer)
        {
                //
                // If lockdown are not requested, then we are done.
                // 
                if ($this->_exam->lockdown->enable == false) {
                        return true;
                }

                /**
                 * TODO: Add client side lockdown logic here.
                 */
                return true;
        }

        /**
         * Get exam lock.
         * 
         * @return Lock
         */
        public function getLock()
        {
                return $this->_lock;
        }

}
