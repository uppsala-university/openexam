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
 * @property string $style Set CSS style.
 * @property string $class Set CSS class.
 * @property boolean $string $display The display mode.
 * @property string $prefix Prefix for CSS classes (defaults to exam).
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DateTime implements Component
{

        /**
         * Arrow between start and end time.
         */
        const ARROW = '&rarr;';
        /**
         * Infinity symbol.
         */
        const INFINITY = '&infin;';
        /**
         * Unknown time.
         */
        const UNKNOWN = '?';

        /**
         * The start time.
         * @var string 
         */
        private $_stime;
        /**
         * The end time.
         * @var string 
         */
        private $_etime;
        /**
         * The start date.
         * @var string 
         */
        private $_sdate;
        /**
         * The end date.
         * @var string 
         */
        private $_edate;

        /**
         * Constructor.
         * @param int $stime The start time.
         * @param int $etime The end time.
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

        public function render()
        {
                if (!isset($this->prefix)) {
                        $this->prefix = 'exam';
                }
                if (!isset($this->display)) {
                        $this->display = 'inline';
                }
                if (!isset($this->class)) {
                        $this->class = 'datetime';
                }
                if (!isset($this->style)) {
                        $this->style = '';
                }

                if ($this->display == false) {
                        $this->display = 'none';
                }

                printf("<span class=\"%s\" style=\"%s;display:%s\">\n", $this->class, $this->style, $this->display);
                printf("<i class=\"fa fa-clock-o\"></i>\n ");

                if ($this->_sdate == $this->_edate) {
                        printf("<span class=\"%s-date\">%s</span>\n", $this->prefix, $this->_sdate);
                        printf("<span class=\"%s-starts\">%s</span>\n", $this->prefix, $this->_stime);
                        printf("%s\n", self::ARROW);
                        printf("<span class=\"%s-starts\">%s</span>\n", $this->prefix, $this->_etime);
                } else {
                        printf("<span class=\"%s-date\">%s</span>\n", $this->prefix, $this->_sdate);
                        printf("<span class=\"%s-starts\">%s</span>\n", $this->prefix, $this->_stime);
                        printf("%s\n", self::ARROW);
                        printf("<span class=\"%s-date\">%s</span>\n", $this->prefix, $this->_edate);
                        printf("<span class=\"%s-starts\">%s</span>\n", $this->prefix, $this->_etime);
                }

                printf("</span>\n");
        }

}
