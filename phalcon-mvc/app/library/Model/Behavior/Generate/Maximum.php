<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Maximum.php
// Created: 2017-04-21 00:44:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior\Generate;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * Maximum unique value generator.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Maximum extends ModelBehavior
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
                        $field = $options['field'];
                        $limit = $options['limit'];

                        $params = array(
                                sprintf("%s = %d", $limit, $model->$limit),
                                'column' => "$field"
                        );
                        $count = sprintf("%s = %d AND %s = %d", $limit, $model->$limit, $field, $model->$field);

                        if (!isset($model->$field)) {
                                $model->$field = $model->maximum($params) + 1;
                        } elseif ($model->count($count) != 0) {
                                $model->$field = $model->maximum($params) + 1;
                        }
                }
        }

}
