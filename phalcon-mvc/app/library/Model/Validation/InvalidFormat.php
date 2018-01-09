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
// File:    InvalidFormat.php
// Created: 2017-10-25 00:47:23
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Model\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

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
         * @param Validation $validator
         * @param string     $attribute
         * @return boolean
         */
        public function validate(Validation $validator, $attribute)
        {
                // 
                // Get bound model:
                // 
                $record = $validator->getEntity();

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
