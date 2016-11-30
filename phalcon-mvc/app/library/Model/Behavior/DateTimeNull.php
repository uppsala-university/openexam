<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DateTimeNull.php
// Created: 2016-11-30 02:30:15
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior;

use Phalcon\Mvc\ModelInterface;

/**
 * Description of DateTimeNull
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
         * @param StudentModel $model The target model.
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
                                $input = $model->$field;

                                // 
                                // Convert literal null's:
                                // 
                                if (is_string($input)) {
                                        if ($input === 'null') {
                                                $value = $input = null;
                                        } elseif ($input === '0000-00-00 00:00:00') {
                                                $value = $input = null;
                                        }
                                }

                                // 
                                // Check conversion of datetime string:
                                // 
                                if (is_string($input)) {
                                        if (!($time = strtotime($input))) {
                                                throw new \Exception("Failed parse datetime string '$input' for $field");
                                        }
                                        if (!($value = date($format, $time))) {
                                                throw new \Exception("Failed print datetime string '$input' for $field");
                                        }
                                        if (is_null($value)) {
                                                throw new \Exception("Input datetime '$input' was translated to null for $field");
                                        }
                                }

                                $model->$field = $value;
                        }
                }
        }

}
