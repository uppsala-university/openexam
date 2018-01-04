<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Ownership.php
// Created: 2014-11-12 23:30:50
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior\Generate;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * Model ownership generator.
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
        public function notify($type, ModelInterface $model)
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
