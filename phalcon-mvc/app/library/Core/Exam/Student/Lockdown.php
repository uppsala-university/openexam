<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Lockdown.php
// Created: 2014-12-17 03:22:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Exam\Student;

use Exception;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Models\Access as AccessModel;
use OpenExam\Models\Computer;
use OpenExam\Models\Exam;
use OpenExam\Models\Lock;
use OpenExam\Models\Room;
use OpenExam\Models\Student;
use Phalcon\Mvc\User\Component;
use UUP\Authentication\Restrictor\AddressRestrictor;

/**
 * Handle client lockdown on student exam access.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Lockdown extends Component
{

        /**
         * Default room name.
         */
        const DEFAULT_ROOM = "unknown";

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
         * @var string 
         */
        private $_remote;

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
                $this->_remote = $this->request->getClientAddress(true);
        }
        
        public function __destruct()
        {
                unset($this->_exam);
                unset($this->_lock);
                unset($this->_remote);
                unset($this->_student);
        }

        /**
         * Check if access is accepted for student. 
         * 
         * This function checks exam state, address restrictions and computer
         * locks. A computer lock is always created, but remains pending for
         * computers not within the list of allowed addresses. The invigilator
         * can then approve the pending lock to grant access.
         * 
         * Returns true if current student should have access to this exam,
         * otherwise return false.
         * 
         * @return int One of the Access::OPEN_XXX constants.
         * @throws \Exception
         */
        public function accepted()
        {
                try {
                        if ($this->checkState() == false) {
                                $this->logger->access->debug(sprintf("Denied access to exam for %s from %s (is not accessable)", $this->_student->user, $this->_remote));
                                return Access::OPEN_DENIED;
                        }
                        if ($this->checkApproved() == true) {
                                $this->logger->access->debug(sprintf("Verified access to exam for %s from %s (id=%d)", $this->_student->user, $this->_remote, $this->_exam->id));
                                return Access::OPEN_APPROVED;
                        }
                        if ($this->checkAddress() == false) {
                                $this->logger->access->notice(sprintf("Pending exam approval for %s from %s (blocked by ip-address)", $this->_student->user, $this->_remote));
                                return Access::OPEN_PENDING;
                        }
                        if ($this->checkLocking() == false) {
                                $this->logger->access->notice(sprintf("Denied access to exam for %s from %s (locking failed)", $this->_student->user, $this->_remote));
                                return Access::OPEN_DENIED;
                        }

                        $this->logger->access->debug(sprintf("Granted access to exam to %s from %s (id=%d)", $this->_student->user, $this->_remote, $this->_exam->id));
                        return Access::OPEN_APPROVED;
                } catch (ModelException $exception) {
                        throw new Exception("Failed query database", Error::SERVICE_UNAVAILABLE, $exception);
                } catch (SecurityException $exception) {
                        $this->logger->access->notice(sprintf("Denied access to exam for %s from %s (%s)", $this->_student->user, $this->_remote, $exception->getMessage()));
                        throw $exception;
                }
        }

        /**
         * Verify exam state.
         * 
         * The exam should only be accessable for students during the exam 
         * start and end time. Once the exam has been closed by the student,
         * it should no longer be possible to open it again.
         * 
         * Returns true if successful, otherwise false.
         * @return boolean
         */
        private function checkState()
        {
                if ($this->_exam->getState()->has(State::RUNNING) == false) {
                        throw new SecurityException("This exam is not open for access", SecurityException::ACCESS);
                }
                if (isset($this->_student->finished)) {
                        throw new SecurityException("Exam has been finished (closed by student) and can't be reopened", SecurityException::ACCESS);
                }

                return true;
        }

        /**
         * Check if approved exam lock exist for remote host.
         * 
         * Return true if lock has been approved and matches remote address,
         * otherwise return false.
         * 
         * @return boolean 
         */
        private function checkApproved()
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
                    ))) == false) {
                        return false;
                }

                // 
                // Check if lock has been approved. If so, verify that it's
                // for remote computer.
                // 
                if ($this->_lock->status != Lock::STATUS_APPROVED) {
                        $this->logger->access->debug(sprintf("Continue open exam check for %s from %s (lock not approved: %s)", $this->_student->user, $this->_remote, $this->_lock->status));
                        return false;
                } elseif ($this->_lock->computer->ipaddr != $this->_remote) {
                        throw new SecurityException(sprintf("This exam is already locked to %s (%s) in %s, %s", $this->_lock->computer->hostname, $this->_lock->computer->ipaddr, $this->_lock->computer->room->name, $this->_lock->computer->room->description), SecurityException::ACCESS);
                }

                // 
                // Lock has been verified.
                // 
                return true;
        }

        /**
         * Verify remote IP-address.
         * 
         * This function verifies that remote IP-address are allowed through
         * address access list. Return true if permitted, otherwise false if
         * the connection is pending approval.
         * 
         * @return boolean
         * @throws ModelException
         */
        private function checkAddress()
        {
                // 
                // Get allowed IP-addresses (single, range or masked network):
                // 
                if (($accesslist = AccessModel::find(array(
                            'columns'    => 'addr',
                            'conditions' => 'exam_id = ?0',
                            'bind'       => array($this->_exam->id)
                    ))) == false) {
                        throw new ModelException(sprintf("Failed lookup access list for exam (id=%d)", $this->_exam->id));
                }

                // 
                // Warn about empty access list. Users should be encourage to
                // explicit define access list.
                // 
                if (count($accesslist) == 0) {
                        $this->logger->access->warning(sprintf("The exam %d has no remote address access list", $this->_exam->id));
                        $this->setLock(Lock::STATUS_APPROVED, $this->getComputer());
                        return true;
                }

                $addresses = array();
                foreach ($accesslist as $access) {
                        $addresses[] = $access->addr;
                }

                // 
                // Check address restriction:
                // 
                $restrictor = new AddressRestrictor($addresses);
                if ($restrictor->match($this->_remote)) {
                        $this->setLock(Lock::STATUS_APPROVED, $this->getComputer());
                        return true;
                } else {
                        $this->setLock(Lock::STATUS_PENDING, $this->getComputer());
                        return false;
                }
        }

        /**
         * Check exam lock.
         * 
         * This function checks that the same exam is not accessed from another
         * computer (if exam is flagged as lockdown). It will also check client
         * side lockdown (on peer computer) if requested.
         * 
         * This function will insert a lock (for tracking) even if the exam
         * is not flagged as lockdown. If flagged as lockdown, then all locking
         * business logic is applied as well.
         * 
         * Returns true if successful, otherwise false.
         * @return boolean
         */
        private function checkLocking()
        {
                // 
                // Get computer object:
                // 
                $computer = $this->getComputer();

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
         * Get remote computer. 
         * 
         * The computer object will be created if missing. If default room
         * is missing, it will also be created. The computer is looked up
         * from IP-address of request (including proxied for). 
         * 
         * @return Computer
         * @throws ModelException
         */
        private function getComputer()
        {
                if (!isset($this->_computer)) {
                        if (($this->_computer = Computer::findFirst(array(
                                    'conditions' => 'ipaddr = ?0',
                                    'bind'       => array($this->_remote)
                            ))) != false) {
                                return $this->_computer;
                        }

                        $this->_computer = new Computer();
                        $this->_computer->room_id = $this->getRoom()->id;
                        $this->_computer->ipaddr = $this->_remote;
                        $this->_computer->port = 0;
                        $this->_computer->password = strtoupper(md5(time()));
                        $this->_computer->hostname = gethostbyaddr($this->_remote);
                        $this->_computer->created = strftime("%x %X");
                        if ($this->_computer->create() == false) {
                                throw new ModelException($this->_computer->getMessages()[0]);
                        }
                }

                return $this->_computer;
        }

        /**
         * Get default computer room.
         * 
         * Returns the default computer room or create new one if missing.
         * 
         * @return Room
         * @throws ModelException
         */
        private function getRoom()
        {
                if (!isset($this->_room)) {
                        if (($this->_room = Room::findFirst(array(
                                    'conditions' => 'name = ?0',
                                    'bind'       => array(self::DEFAULT_ROOM)
                            ))) != false) {
                                return $this->_room;
                        }

                        $this->_room = new Room();
                        $this->_room->name = self::DEFAULT_ROOM;
                        $this->_room->description = "Default room for ungrouped computers";
                        if ($this->_room->create() == false) {
                                throw new ModelException($this->_room->getMessages()[0]);
                        }
                }

                return $this->_room;
        }

        /**
         * Create or update computer lock with status $status.
         * @param string $status The lock status.
         * @param Computer $computer The computer 
         */
        private function setLock($status, $computer)
        {
                if ($this->_lock == false) {
                        $this->_lock = new Lock();
                        $this->_lock->exam_id = $this->_exam->id;
                        $this->_lock->student_id = $this->_student->id;
                        $this->_lock->computer_id = $computer->id;
                        $this->_lock->status = $status;
                } else {
                        $this->_lock->computer_id = $computer->id;
                        $this->_lock->status = $status;
                }

                if ($this->_lock->save() == false) {
                        throw new ModelException($this->_lock->getMessages()[0]);
                } else {
                        $this->logger->access->debug(sprintf("Wrote %s exam access lock for %s from %s (id=%d)", $status, $this->_student->user, $this->_remote, $this->_exam->id));
                }
        }

}
