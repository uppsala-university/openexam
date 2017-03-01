<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Sequence.php
// Created: 2017-03-01 20:48:29
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Validation;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Validator;
use Phalcon\Mvc\Model\ValidatorInterface;

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
         * @param EntityInterface $record
         * @param string $attribute
         * @return boolean
         */
        public function validate(EntityInterface $record)
        {
                // 
                // Validate datetime by default:
                // 
                $type = $this->getOption("type", "datetime");

                // 
                // Support passing sequence that can be used as a fence:
                // 
                $sequence = $this->getOption("sequence", array());
                $property = $this->getOption("field");

                // 
                // Iterate thru all properties:
                // 
                if ($type == "datetime") {
                        foreach ($property as $index => $prop) {
                                if (($value = strtotime($record->$prop))) {
                                        $sequence[$index] = $value;
                                }
                        }
                }

                // 
                // Validate sequence of values:
                // 
                for ($prev = 0, $i = 0; $i < count($sequence); ++$i) {
                        if ($sequence[$i] < $prev) {
                                $this->appendMessage($this->getOption("message"));
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
