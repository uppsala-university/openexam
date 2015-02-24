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
        private $lock;
        /**
         * @var Exam 
         */
        private $exam;
        /**
         * @var Student 
         */
        private $student;
        /**
         * @var string 
         */
        private $remote;

        /**
         * Constructor.
         * @param Exam $exam The current exam.
         * @param Student $student The current student.
         */
        public function __construct($exam, $student)
        {
                $this->lock = false;
                $this->exam = $exam;
                $this->student = $student;
                $this->remote = $this->request->getClientAddress(true);
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
         * @throws Exception
         */
        public function accepted()
        {
                if ($this->checkState() == false) {
                        $this->logger->auth->notice(sprintf("Denied access for %s (exam is not accessable)", $this->remote));
                        return Access::OPEN_DENIED;
                }
                if ($this->checkApproved() == true) {
                        $this->logger->auth->debug(sprintf("Verified access for %s@%s on exam (id=%d)", $this->student->user, $this->remote, $this->exam->id));
                        return Access::OPEN_APPROVED;
                }
                if ($this->checkAddress() == false) {
                        $this->logger->auth->notice(sprintf("Denied access for %s (blocked by ip-address)", $this->remote));
                        return Access::OPEN_PENDING;
                }
                if ($this->checkLocking() == false) {
                        $this->logger->auth->notice(sprintf("Denied access for %s (exam locking failed)", $this->remote));
                        return Access::OPEN_DENIED;
                }

                $this->logger->auth->debug(sprintf("Granted access for %s@%s on exam (id=%d)", $this->student->user, $this->remote, $this->exam->id));
                return Access::OPEN_APPROVED;
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
                if ($this->exam->getState()->has(State::RUNNING) == false) {
                        return false;
                }
                if (isset($this->student->finished)) {
                        return false;
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
                // Get existing lock:
                // 
                if (($this->lock = Lock::findFirst(array(
                            'conditions' => 'exam_id = ?0 AND student_id = ?1',
                            'bind'       => array($this->exam->id, $this->student->id)
                    ))) == false) {
                        return false;
                }

                // 
                // Check if lock has been approved:
                // 
                if ($this->lock->status != Lock::STATUS_APPROVED) {
                        return false;
                }

                // 
                // Check that lock matches remote address:
                // 
                if ($this->lock->computer->ipaddr != $this->remote) {
                        return false;
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
                            'bind'       => array($this->exam->id)
                    ))) == false) {
                        $this->logger->system->error(sprintf("Failed lookup access list for exam (id=%d)", $this->exam->id));
                        throw new ModelException("Failed find access list for exam");
                }

                $addresses = array();
                foreach ($accesslist as $access) {
                        $addresses[] = $access->addr;
                }

                // 
                // Check address restriction:
                // 
                $restrictor = new AddressRestrictor($addresses);
                if ($restrictor->match($this->remote)) {
                        return true;
                }

                // 
                // Add pending exam lock:
                // 
                $computer = $this->getComputer();
                $this->setLock(Lock::STATUS_PENDING, $computer);

                return false;
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
                // Create lock of missing:
                // 
                if ($this->lock == false) {
                        $this->setLock(Lock::STATUS_APPROVED, $computer);
                }

                //
                // If lockdown are not requested, then we are done.
                // 
                if ($this->exam->lockdown == false) {
                        return true;
                }

                // 
                // Check that exam is not opened from another computer:
                // 
                if ($this->lock->computer->ipaddr != $computer->ipaddr) {
                        $this->logger->system->error(sprintf("Student %s@%s tried to open exam already open from %s (id=%d)", $this->student->user, $this->remote, $this->lock->computer->ipaddr, $this->exam->id));
                        throw new SecurityException(sprintf("Exam has already been opened from %s", $this->lock->computer->ipaddr));
                }

                // 
                // TODO: Add client side lockdown logic here.
                // 

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
                // 
                // Lookup computer from IP-address (including proxied for):
                // 
                if (($computer = Computer::findFirst(array(
                            'conditions' => 'ipaddr = ?0',
                            'bind'       => array($this->remote)
                    ))) != false) {
                        return $computer;
                }

                // 
                // Computer don't exist. Try to get default room:
                // 
                if (($room = Room::findFirst(array(
                            'conditions' => 'name = ?0',
                            'bind'       => array(self::DEFAULT_ROOM)
                    ))) == false) {
                        // 
                        // The default room is missing. Create new:
                        // 
                        $room = new Room();
                        $room->name = self::DEFAULT_ROOM;
                        $room->description = "Default room for ungrouped computers";
                        if ($room->create() == false) {
                                throw new ModelException($room->getMessages()[0]);
                        }
                }

                // 
                // Create new computer object:
                // 
                $computer = new Computer();
                $computer->room_id = $room->id;
                $computer->ipaddr = $this->remote;
                $computer->hostname = gethostbyaddr($this->remote);
                $computer->created = strftime("%x %X");
                if ($computer->create() == false) {
                        throw new ModelException($computer->getMessages()[0]);
                }

                return $computer;
        }

        /**
         * Create or update computer lock with status $status.
         * @param string $status The lock status.
         * @param Computer $computer The computer 
         */
        private function setLock($status, $computer)
        {
                if ($this->lock == false) {
                        $this->lock = new Lock();
                        $this->lock->exam_id = $this->exam->id;
                        $this->lock->student_id = $this->student->id;
                        $this->lock->computer_id = $computer->id;
                        $this->lock->status = $status;
                } else {
                        $this->lock->status = $status;
                }

                if ($this->lock->save() == false) {
                        throw new ModelException($lock->getMessages()[0]);
                }
        }

}
