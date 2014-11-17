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
         * Invoke callback function in trusted context.
         * @param callable $callback The callback function.
         * @param DiInterface $dependencyInjector
         */
        protected function trustedContextCall($callback, $dependencyInjector = null)
        {
                if (!isset($dependencyInjector)) {
                        $dependencyInjector = \Phalcon\DI::getDefault();
                }

                $this->logger = $dependencyInjector->get('logger');
                $this->user = $dependencyInjector->get('user');

                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

                $trace['user'] = $this->user->getPrincipalName();
                $trace['role'] = $this->user->getPrimaryRole();

                $this->logger->auth->debug(print_r($trace, true));

                $role = $this->user->setPrimaryRole(Roles::TRUSTED);
                $result = $callback($this->user);
                $this->user->setPrimaryRole($role);

                return $result;
        }

}
