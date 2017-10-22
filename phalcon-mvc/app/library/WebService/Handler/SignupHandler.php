<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SignupHandler.php
// Created: 2015-03-13 16:28:52
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Handler;

use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\Signup\Student;
use OpenExam\Library\Security\Signup\Teacher;
use OpenExam\Library\Security\User;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;
use Phalcon\Config;

/**
 * Signup service handler.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SignupHandler extends ServiceHandler
{

        /**
         * The teacher signup object.
         * @var Teacher 
         */
        private $_teacher;
        /**
         * The student signup object.
         * @var Student
         */
        private $_student;
        /**
         * The signup config.
         * @var Config 
         */
        private $_config;

        /**
         * Constructor.
         * @param ServiceRequest $request The service request.
         * @param User $user The logged in user.
         * @param Config $config The signup config.
         */
        public function __construct($request, $user, $config)
        {
                parent::__construct($request, $user);

                $this->_teacher = new Teacher($user->getPrincipalName());
                $this->_student = new Student($user->getPrincipalName());

                $this->_config = $config;
        }

        /**
         * Add teacher role to caller.
         * 
         * This function can only be called by users having the employee
         * affiliation, otherwise an exception is throwed.
         * 
         * @return ServiceResponse
         * @throws SecurityException
         */
        public function insert()
        {
                if ($this->_user->affiliation->isEmployee()) {
                        return new ServiceResponse($this, self::SUCCESS, $this->_teacher->insert());
                } else {
                        throw new SecurityException("Only employees can subscribe as teachers", SecurityException::ACTION);
                }
        }

        /**
         * Remove teacher role from caller.
         * 
         * This function can only be called by users having the employee
         * affiliation, otherwise an exception is throwed.
         * 
         * @return ServiceResponse
         * @throws SecurityException
         */
        public function remove()
        {
                if ($this->_user->affiliation->isEmployee()) {
                        return new ServiceResponse($this, self::SUCCESS, $this->_teacher->remove());
                } else {
                        throw new SecurityException("Only employees can subscribe as teachers", SecurityException::ACTION);
                }
        }

        /**
         * Subcribe to examinations.
         * 
         * The exams to subscribe to is either taken from service request or
         * passed as argument to this function. 
         * 
         * Returns service response containing the exams that has been 
         * subscribed as keys. The values will be false for already subscribed 
         * exams and true if being newly assigned.
         * 
         * Examples:
         * <code>
         * // 
         * // Subscribe as teacher and student on these exams:
         * // 
         * $handler->subscribe(array(
         *      'teacher' => array(1, 2, 3),
         *      'student' => array(4, 5, 6)
         * ));
         * 
         * // 
         * // Subscribe as teacher on all (from config) available exams:
         * // 
         * $handler->subscribe(array(
         *      'teacher' => true
         * ));
         * 
         * // 
         * // Subscribe as student and teacher on all (from config) available 
         * // exams:
         * // 
         * $handler->subscribe(array());
         * 
         * // 
         * // Same as previous, but relies on empty request:
         * // 
         * $handler->subscribe();
         * </code>
         * 
         * @param array $data The exams to assign.
         * @return ServiceResponse
         * @throws SecurityException
         */
        public function subscribe($data = null)
        {
                $result = array('teacher' => array(), 'student' => array());

                // 
                // Use service request if argument is missing:
                // 
                if (!isset($data)) {
                        $data = $this->_request->data;
                }

                // 
                // Handle 'teacher' => true or 'student' => true:
                // 
                if (isset($data['teacher']) && is_bool($data['teacher'])) {
                        $data['teacher'] = $this->_config->teacher->toArray();
                }
                if (isset($data['student']) && is_bool($data['student'])) {
                        $data['student'] = $this->_config->student->toArray();
                }

                // 
                // Assign all available exams if called without params:
                // 
                if (count($data) == 0) {
                        $data = $this->_config->toArray();
                }

                // 
                // Strip teacher exams if caller is not employee:
                // 
                if ($this->_user->affiliation->isEmployee() == false) {
                        if (isset($data['teacher'])) {
                                unset($data['teacher']);
                        }
                }

                // 
                // Check that requested subscription is enabled:
                // 
                if (isset($data['teacher']) && $this->_teacher->isEnabled() == false) {
                        throw new SecurityException("Teacher subscription has been disabled", SecurityException::ACTION);
                }
                if (isset($data['student']) && $this->_student->isEnabled() == false) {
                        throw new SecurityException("Student subscription has been disabled", SecurityException::ACTION);
                }

                // 
                // Assign teacher exams:
                // 
                if (isset($data['teacher'])) {
                        foreach ($data['teacher'] as $index) {
                                $result['teacher'][$index] = $this->_teacher->assign($index);
                        }
                }

                // 
                // Assign student exams:
                // 
                if (isset($data['student'])) {
                        foreach ($data['student'] as $index) {
                                $result['student'][$index] = $this->_student->assign($index);
                        }
                }

                // 
                // Return service response containing
                // 
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

}
