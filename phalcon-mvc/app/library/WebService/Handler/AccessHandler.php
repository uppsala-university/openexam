<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AccessHandler.php
// Created: 2015-01-29 11:37:52
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Handler;

use OpenExam\Library\Core\Exam\Student\Access;
use OpenExam\Library\Core\Location;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;
use OpenExam\Models\Exam;
use OpenExam\Models\Lock;

/**
 * Access service handler.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AccessHandler extends ServiceHandler
{

        /**
         * @var Exam 
         */
        private $exam;
        /**
         * @var Lock 
         */
        private $lock;
        /**
         * @var Access 
         */
        private $access;

        /**
         * Constructor.
         * @param ServiceRequest $request The service request.
         * @param User $user The logged in user.
         */
        public function __construct($request, $user)
        {
                parent::__construct($request, $user);

                if (isset($request->data['exam_id'])) {
                        if (($this->exam = Exam::findFirstById($request->data['exam_id'])) == false) {
                                throw new SecurityException("No exam found!", self::UNDEFINED);
                        }
                }
                if (isset($request->data['lock_id'])) {
                        if (($this->lock = Lock::findFirstById($request->data['lock_id'])) == false) {
                                throw new SecurityException("No lock found!", self::UNDEFINED);
                        }
                }

                $this->access = new Access($this->exam);
        }

        /**
         * Open exam.
         * @return ServiceResponse
         */
        public function open()
        {
                if (!isset($this->exam)) {
                        return new ServiceResponse($this, self::UNDEFINED, "Undefined exam object.");
                }

                $this->user->setPrimaryRole(Roles::STUDENT);
                $status = $this->access->open($this->exam);

                if ($status == Access::OPEN_APPROVED) {
                        return new ServiceResponse($this, self::SUCCESS, true);
                } else if ($status == Access::OPEN_PENDING) {
                        return new ServiceResponse($this, self::PENDING, "The exam connection is pending approval.");
                } else if ($status == Access::OPEN_DENIED) {
                        return new ServiceResponse($this, self::FORBIDDEN, "Permission denied.");
                } else {
                        return new ServiceResponse($this, self::UNDEFINED, "Unhandled open mode.");
                }
        }

        /**
         * Close exam lock.
         * @return ServiceResponse
         */
        public function close()
        {
                if (!isset($this->exam)) {
                        return new ServiceResponse($this, self::UNDEFINED, "Undefined exam object.");
                }

                $this->user->setPrimaryRole(Roles::STUDENT);
                $status = $this->access->close($this->exam);

                if ($status == true) {
                        return new ServiceResponse($this, self::SUCCESS, true);
                } else {
                        return new ServiceResponse($this, self::FORBIDDEN, "Permission denied.");
                }
        }

        /**
         * Approve exam lock.
         * @return ServiceResponse
         */
        public function approve()
        {
                if (!isset($this->lock)) {
                        return new ServiceResponse($this, self::UNDEFINED, "Undefined lock object.");
                }

                $this->user->setPrimaryRole(Roles::INVIGILATOR);
                $this->access->approve($this->lock);

                return new ServiceResponse($this, self::SUCCESS, true);
        }

        /**
         * Release exam lock.
         * @return ServiceResponse
         */
        public function release()
        {
                if (!isset($this->lock)) {
                        return new ServiceResponse($this, self::UNDEFINED, "Undefined lock object.");
                }

                $this->user->setPrimaryRole(Roles::INVIGILATOR);
                $this->access->release($this->lock);

                return new ServiceResponse($this, self::SUCCESS, true);
        }

        /**
         * Get location/access information entries.
         * @param Location $location The location and information service.
         * @param string $section The section (active, system or recent).
         * @return ServiceResponse
         */
        public function entries($location, $section = null)
        {
                if (!isset($this->request->data['exam_id'])) {
                        $this->request->data['exam_id'] = 0;
                }
                if (isset($section)) {
                        $this->request->params['filter'] = array(
                                $section => true
                        );
                }
                if (!isset($this->request->params['filter'])) {
                        $this->request->params['filter'] = array(
                                'system' => true,
                                'recent' => true,
                                'active' => true
                        );
                }
                if (!isset($this->request->params['flat'])) {
                        $this->request->params['flat'] = false;
                }
                
                print_r($this->request);

                $result = $location->getEntries(
                    $this->request->data['exam_id'], $this->request->params['filter'], $this->request->params['flat']
                );
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

}
