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
// File:    UUID.php
// Created: 2014-12-02 15:25:21
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior\Generate;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Security\Random;

/**
 * Universally unique identifier (UUID) generator.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UUID extends ModelBehavior
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
                                $rand = new Random();

                                if ($options['force']) {
                                        $model->$name = $rand->uuid();
                                } elseif (!isset($model->$name)) {
                                        $model->$name = $rand->uuid();
                                }

                                return true;
                        }, $model->getDI());
                }
        }

}
