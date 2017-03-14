<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelBehavior.php
// Created: 2014-11-13 01:41:59
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior;

use OpenExam\Library\DI\Logger;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\BehaviorInterface;

/**
 * Base class for model behavior.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelBehavior extends Behavior implements BehaviorInterface
{

        /**
         * The logger service.
         * @var Logger 
         */
        protected $logger;
        /**
         * The authenticated user.
         * @var User 
         */
        protected $user;
        /**
         * The primary role.
         * @var string 
         */
        protected $role;

        /**
         * Setup for making trusted call.
         * @param DiInterface $dependencyInjector
         */
        private function setup($dependencyInjector)
        {
                if (!isset($dependencyInjector)) {
                        $dependencyInjector = \Phalcon\DI::getDefault();
                }

                $this->logger = $dependencyInjector->getLogger();
                $this->user = $dependencyInjector->getUser();
                $this->role = $this->user->getPrimaryRole();

                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

                $trace['user'] = $this->user->getPrincipalName();
                $trace['role'] = $this->user->getPrimaryRole();

                $this->logger->access->debug(print_r($trace, true));

                $this->user->setPrimaryRole(Roles::TRUSTED);
        }

        /**
         * Leave after making trusted call.
         */
        private function leave()
        {
                $this->user->setPrimaryRole($this->role);
        }

        /**
         * Invoke callback function in trusted context.
         * @param callable $callback The callback function.
         * @param DiInterface $dependencyInjector
         */
        protected function trustedContextCall($callback, $dependencyInjector = null)
        {
                try {
                        $this->setup($dependencyInjector);
                        $result = $callback($this->user, $this->role);
                } catch (\Exception $exception) {
                        $this->logger->access->error($exception->getMessage());
                        throw $exception;
                } finally {
                        $this->leave();
                }

                return $result;
        }

}
