<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    InvalidFormat.php
// Created: 2017-10-25 00:47:23
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Model\Validation;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Validator;
use Phalcon\Mvc\Model\ValidatorInterface;

/**
 * Input data validation.
 * 
 * Use this class to validate that input data is not invalid. Could i.e. be 
 * used to prevent empty JSON object from being stored as score. 
 *
 * @author Anders Lövgren (QNET)
 */
class InvalidFormat extends Validator implements ValidatorInterface
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
                // Get field to validate and invalid input:
                // 
                $field = $this->getOption("field");
                $input = $this->getOption("input", array("{}", ""));

                // 
                // Get message (if any):
                // 
                if (!($message = $this->getOption("message", false))) {
                        $message = sprintf("Invalid input %s for %s", $record->$field, $field);
                }

                // 
                // Support using string as input:
                // 
                if (is_string($input)) {
                        $input = array($input);
                }

                // 
                // Check that input data isn't invalid:
                // 
                if (in_array($record->$field, $input)) {
                        $this->appendMessage($message);
                        return false;
                }

                // 
                // Input data is OK:
                // 
                return true;
        }

}
