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
// File:    Sequence.php
// Created: 2017-03-01 20:48:29
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

/**
 * Sequence validation.
 * 
 * Use this class to validate that a sequence of fields has an increasing
 * serie of values. For example, that starttime < endtime.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Sequence extends Validator implements ValidatorInterface
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
                // Validate datetime by default:
                // 
                $type = $this->getOption("type", "datetime");

                // 
                // Support passing sequence that can be used as a fence:
                // 
                $sequence = $this->getOption("sequence", array());

                // 
                // Iterate thru all properties:
                // 
                if ($type == "datetime") {
                        foreach ($sequence as $index => $prop) {
                                if (($value = strtotime($record->$prop))) {
                                        $sequence[$index] = $value;
                                }
                        }
                }

                // 
                // Validate sequence of values:
                // 
                for ($prev = 0, $i = 0; $i < count($sequence); ++$i) {
                        if (!isset($sequence[$i])) {
                                continue;
                        } elseif ($sequence[$i] < $prev) {
                                $message = $this->getOption("message");
                                $validator->appendMessage(new Message($message, $attribute));
                                return false;
                        } else {
                                $prev = $sequence[$i];
                        }
                }

                // 
                // Whole sequence passed:
                // 
                return true;
        }

}
