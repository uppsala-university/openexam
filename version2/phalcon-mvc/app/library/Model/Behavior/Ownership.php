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

use Phalcon\Mvc\ModelInterface;

/**
 * Ownership behavior for models.
 * 
 * This behavior can be used to set an ownership property in the model to
 * either supplied owner or the current authenticated user if the owner 
 * option is missing. 
 * 
 * The ownership can be enforced by setting the force option, otherwise it 
 * will only apply if owner property is unset.
 * 
 * <code>
 * $this->addBehavior(new Ownership(array(
 *      'beforeValidationOnCreate' => array(
 *              'field' => 'user',      // Name of ownership property.
 *              'force' => true,        // Always overwrite property.
 *              'owner' => 'username'   // Set owner property to username.
 *      )
 * )));
 * </code>
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Ownership extends ModelBehavior
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, $model)
        {
                if (($options = $this->getOptions($type))) {
                        $this->trustedContextCall(function($caller) use($model, $options) {

                                $name = $options['field'];

                                if (isset($options['owner'])) {
                                        $user = $options['owner'];
                                } else {
                                        $user = $caller->getPrincipalName();
                                }
                                
                                if (!isset($user)) {
                                        return false;   // not authenticated
                                } elseif ($options['force']) {
                                        $model->$name = $user;
                                } elseif (!isset($model->$name)) {
                                        $model->$name = $user;
                                }

                                return true;
                        }, $model->getDI());
                }
        }

}
