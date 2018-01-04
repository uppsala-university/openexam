<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    DateTimeNull.php
// Created: 2016-11-30 02:30:15
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior\Transform;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use OpenExam\Library\Model\Exception;
use Phalcon\Mvc\ModelInterface;

/**
 * DateTime null behavior.
 * 
 * Converts "null" datetime values on requested fields to true null values 
 * to fix problem arising from javascript passing i.e. literal 'null' instead
 * of null. 
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DateTimeNull extends ModelBehavior
{

        /**
         * Default datetime format.
         */
        const FORMAT = 'Y-m-d H:i:s';

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {
                        // 
                        // Use default datetime format if missing:
                        // 
                        if (!isset($options['format'])) {
                                $format = self::FORMAT;
                        } else {
                                $format = $options['format'];
                        }

                        // 
                        // Always work on array of fields:
                        // 
                        if (is_string($options['field'])) {
                                $fields = array($options['field']);
                        } else {
                                $fields = $options['field'];
                        }

                        // 
                        // Check all fields:
                        // 
                        foreach ($fields as $field) {
                                $input = $value = $model->$field;

                                // 
                                // Convert literal null's:
                                // 
                                if (is_string($input)) {
                                        if ($input === 'null') {
                                                $value = $input = null;
                                        } elseif ($input === '0000-00-00 00:00:00') {
                                                $value = $input = null;
                                        } elseif (strlen(trim($input)) == 0) {
                                                $value = $input = null;
                                        }
                                }

                                // 
                                // Check conversion of datetime string:
                                // 
                                if (is_string($input)) {
                                        if (!($time = strtotime($input))) {
                                                throw new Exception("Failed parse datetime string '$input' for $field");
                                        }
                                        if (!($value = date($format, $time))) {
                                                throw new Exception("Failed print datetime string '$input' for $field");
                                        }
                                        if (is_null($value)) {
                                                throw new Exception("Input datetime '$input' was translated to null for $field");
                                        }
                                }

                                $model->$field = $value;
                        }
                }
        }

}
