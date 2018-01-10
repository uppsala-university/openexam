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
// File:    DateTime.php
// Created: 2017-10-25 11:04:02
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

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
                // Get current values:
                // 
                if ($this->hasOption("current")) {
                        $current = $this->getOption("current");
                } else {
                        $current = array();
                }

                // 
                // Get grace period in seconds:
                // 
                if ($this->hasOption("grace")) {
                        $grace = $this->getOption("grace");
                } else {
                        $grace = 60;
                }

                // 
                // Set defaults for current:
                // 
                if (!isset($current[$attribute])) {
                        $current[$attribute] = false;
                }

                // 
                // Check that datetime is null or in future:
                // 
                if (empty($record->$attribute)) {
                        return true;
                }
                if ($current[$attribute] == $record->$attribute) {
                        return true;
                }
                if (strtotime($current[$attribute]) == strtotime($record->$attribute)) {
                        return true;
                }
                if (!($time = strtotime($record->$attribute))) {
                        $message = sprintf("Failed parse %s to timestamp", $record->$attribute);
                        $validator->appendMessage(new Message($message, $attribute));
                        return false;
                }
                if ($time + $grace < time()) {
                        $message = sprintf("The datetime string '%s' for %s has a value in the past", $record->$attribute, $attribute);
                        $validator->appendMessage(new Message($message, $attribute));
                        return false;
                }

                // 
                // Input data is OK:
                // 
                return true;
        }

}
