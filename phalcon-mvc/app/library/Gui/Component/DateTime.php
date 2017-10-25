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
 * @property-read string $edate The end date.
 * @property-read string $etime The end time.
 * @property-read string $sdate The start date.
 * @property-read string $stime The start time.
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
         * The start date.
         * @var string 
         */
        protected $_sdate;
        /**
         * The end date.
         * @var string 
         */
        protected $_edate;

        /**
         * Constructor.
         * @param int|string $stime The start time.
         * @param int|string $etime The end time.
         */
        public function __construct($stime = null, $etime = null)
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

                if (isset($this->_stime)) {
                        $this->_sdate = strftime("%x", $this->_stime);
                        $this->_stime = strftime("%H:%M", $this->_stime);
                } else {
                        $this->_sdate = self::UNKNOWN;
                }

                if (isset($this->_etime)) {
                        $this->_edate = strftime("%x", $this->_etime);
                        $this->_etime = strftime("%H:%M", $this->_etime);
                } else {
                        $this->_edate = self::INFINITY;
                }
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'edate':
                                return $this->_edate;
                        case 'etime':
                                return $this->_etime;
                        case 'sdate':
                                return $this->_sdate;
                        case 'stime':
                                return $this->_stime;
                }
        }

}
