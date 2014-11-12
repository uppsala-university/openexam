<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Ownership.php
// Created: 2014-11-12 23:30:50
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior;

use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\BehaviorInterface;

/**
 * Ownership behavior for models.
 * 
 * <code>
 * $this->addBehavior(new Ownership(array(
 *      'beforeValidationOnCreate' => array(
 *              'field' => 'user',
 *              'force' => true         // Always set
 *      )
 * )));
 * </code>
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Ownership extends Behavior implements BehaviorInterface
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $eventType
         * @param Phalcon\Mvc\ModelInterface $model
         */
        public function notify($type, $model)
        {
                if (($options = $this->getOptions($type))) {
                        $user = $model->getDI()->get('user');
                        $name = $options['field'];

                        if ($options['force']) {
                                $model->$name = $user->getPrincipalName();
                        } elseif (!isset($model->$name)) {
                                $model->$name = $user->getPrincipalName();
                        }
                }
        }

}
