<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DateTime.php
// Created: 2016-10-27 11:55:16
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Gui\Component;

use OpenExam\Library\Gui\Component;

/**
 * Datetime component.
 * 
 * @property-read int $stime The start time (timestamp).
 * @property-read int $etime The end time (timestamp).
 * 
 * @property-read string $startdate The start date.
 * @property-read string $starttime The start time.
 * 
 * @property-read string $enddate The end date.
 * @property-read string $endtime The end time.
 * 
 * @property string $style Set CSS style.
 * @property string $class Set CSS class.
 * @property boolean $display The display mode.
 * @property string $prefix Prefix for CSS classes (defaults to exam).
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class DateTime implements Component
{

        /**
         * Infinity symbol.
         */
        const INFINITY = '&infin;';
        /**
         * Arrow between start and end time.
         */
        const ARROW = '&rarr;';
        /**
         * Unknown time.
         */
        const UNKNOWN = '?';

        /**
         * The start time.
         * @var string 
         */
        protected $_stime;
        /**
         * The end time.
         * @var string 
         */
        protected $_etime;

        /**
         * Constructor.
         * @param int|string $stime The start time.
         * @param int|string $etime The end time.
         */
        public function __construct($stime = false, $etime = false)
        {
                if (is_string($stime)) {
                        $this->_stime = strtotime($stime);
                } else {
                        $this->_stime = $stime;
                }

                if (is_string($etime)) {
                        $this->_etime = strtotime($etime);
                } else {
                        $this->_etime = $etime;
                }
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'stime':
                                return $this->_stime;
                        case 'etime':
                                return $this->_etime;
                        case 'startdate':
                                if ($this->_stime) {
                                        return $this->startdate = strftime("%x", $this->_stime);
                                } else {
                                        return $this->startdate = self::UNKNOWN;
                                }
                        case 'starttime':
                                if ($this->_stime) {
                                        return $this->starttime = strftime("%H:%M", $this->_stime);
                                } else {
                                        return $this->starttime = null;
                                }
                        case 'enddate':
                                if ($this->_etime) {
                                        return $this->enddate = strftime("%x", $this->_etime);
                                } else {
                                        return $this->enddate = self::INFINITY;
                                }
                        case 'endtime':
                                if ($this->_etime) {
                                        return $this->endtime = strftime("%H:%M", $this->_etime);
                                } else {
                                        return $this->endtime = null;
                                }
                }
        }

}
