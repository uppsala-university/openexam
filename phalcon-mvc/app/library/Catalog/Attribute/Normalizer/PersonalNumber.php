<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PersonalNumber.php
// Created: 2017-04-06 13:13:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute\Normalizer;

use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Core\Pattern;

/**
 * Personal number class.
 * 
 * @property-read string $year The birth year (YYYY).
 * @property-read string $month The birth month (MM) with leading zero.
 * @property-read string $day The birth day (DD) with leading zero.
 * @property-read string $birth The birth day (YYYYMMDD).
 * @property-read string $serial The serial part.
 * @property-read string $checksum The checksum value.
 * 
 * @property-read boolean $male Is person of male sex?
 * @property-read boolean $foreign Is person from a foreign country?
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class PersonalNumber
{

        /**
         * The personal number (on YYYYMMMDDNNNN form).
         * @var string 
         */
        private $_persnr;

        /**
         * Constructor.
         * @param string $persnr The personal number.
         */
        public function __construct($persnr)
        {
                // 
                // Use common personal number pattern:
                // 
                $matches = array();
                $pattern = Pattern::get(Pattern::MATCH_PERSNR);

                if (!preg_match($pattern, $persnr, $matches)) {
                        throw new Exception("Unexpected personal number format $persnr");
                }

                // 
                // Convert to YYYYMMDDNNNN standard format:
                // 
                if (strlen($matches[1]) == 8) {
                        $this->_persnr = sprintf("%s%s", $matches[1], $matches[2]);
                } elseif (date('y') < substr($matches[1], 0, 2)) {
                        $this->_persnr = sprintf("19%s%s", $matches[1], $matches[2]);
                } else {
                        $this->_persnr = sprintf("20%s%s", $matches[1], $matches[2]);
                }
        }

        public function __toString()
        {
                return $this->getFormatted();
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'year':
                                return substr($this->_persnr, 0, 4);
                        case 'month':
                                return substr($this->_persnr, 4, 2);
                        case 'day':
                                return substr($this->_persnr, 6, 2);
                        case 'birth':
                                return substr($this->_persnr, 0, 8);
                        case 'serial':
                                return substr($this->_persnr, 8, 3);
                        case 'checksum':
                                return $this->_persnr[11];
                        case 'male':
                                return $this->isMale();
                        case 'foreign':
                                return $this->isForeign();
                }
        }

        /**
         * The number represents a male person.
         * @return boolean
         */
        public function isMale()
        {
                return intval($this->_persnr[11]) % 2 == 1;
        }

        /**
         * The number represents a female person.
         * @return boolean
         */
        public function isFemale()
        {
                return $this->isMale() == false;
        }

        /**
         * The number represents a foreign person.
         * @return boolean
         */
        public function isForeign()
        {
                return (is_numeric($this->_persnr[8]) && is_numeric($this->_persnr[11])) === false;
        }

        /**
         * Get common format (YYMMDD-NNNN).
         * @return string
         */
        public function getFormatted()
        {
                return sprintf("%s-%s", substr($this->_persnr, 2, 6), substr($this->_persnr, 8, 4));
        }

        /**
         * Get normalized personal number.
         * @return string
         */
        public function getNormalized()
        {
                return $this->_persnr;
        }

}
