<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DateTime.php
// Created: 2017-10-25 11:04:02
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Validation;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Validator;
use Phalcon\Mvc\Model\ValidatorInterface;

/**
 * Validate datetime.
 * 
 * Ensure that datetime is either null or an timestamp in future. Use this
 * validator to prevent setting schedule on exam or student in past.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DateTime extends Validator implements ValidatorInterface
{

        /**
         * Executes the validation
         *
         * @param EntityInterface $record
         * @param string $attribute
         * @return boolean
         */
        public function validate(EntityInterface $record)
        {
                // 
                // Get field to validate:
                // 
                if ($this->isSetOption("field")) {
                        $fields = $this->getOption("field");
                } else {
                        $fields = array("starttime", "endtime");
                }

                // 
                // Get current values:
                // 
                if ($this->isSetOption("current")) {
                        $current = $this->getOption("current");
                } else {
                        $current = array();
                }

                // 
                // Support using string as field name:
                // 
                if (is_string($fields)) {
                        $fields = array($fields);
                }

                // 
                // Set defaults for current:
                // 
                foreach ($fields as $field) {
                        if (!isset($current[$field])) {
                                $current[$field] = false;
                        }
                }

                // 
                // Check that datetime is null or in future:
                // 
                foreach ($fields as $field) {
                        if (empty($record->$field)) {
                                continue;
                        }
                        if ($current[$field] == $record->$field) {
                                continue;
                        }
                        if (!($time = strtotime($record->$field))) {
                                $message = sprintf("Failed parse %s to timestamp", $record->$field);
                                $this->appendMessage($message);
                                return false;
                        }
                        if ($time < time()) {
                                $message = sprintf("The datetime string '%s' for %s has a value in the past", $record->$field, $field);
                                $this->appendMessage($message);
                                return false;
                        }
                }

                // 
                // Input data is OK:
                // 
                return true;
        }

}
