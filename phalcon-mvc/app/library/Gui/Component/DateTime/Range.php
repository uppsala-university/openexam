<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Range.php
// Created: 2017-10-25 04:27:44
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Gui\Component\DateTime;

use OpenExam\Library\Gui\Component\DateTime;

/**
 * Range datetime component.
 *
 * <code>
 * $datetime = new DateTime('2017-10-24 11:45', '2017-10-24 14:00');
 * $datetime->format = 'range';
 * $datetime->render();         // 2017-10-24 11:45 -> 14:00
 * 
 * $datetime = new DateTime('2017-10-24 11:45', '2017-10-25 14:00');
 * $datetime->format = 'range';
 * $datetime->render();         // 2017-10-24 11:45 -> 2017-10-25 14:00
 * </code>
 * 
 * @author Anders Lövgren (QNET)
 */
class Range extends DateTime
{

        /**
         * Get datetime text.
         * @return string
         */
        public function text()
        {
                if ($this->_stime == false && $this->_etime == false) {
                        return sprintf("%s -> %s", $this->startdate, $this->enddate);
                } elseif ($this->_stime == false) {
                        return sprintf("%s -> %s %s", $this->startdate, $this->enddate, $this->endtime);
                } elseif ($this->_etime == false) {
                        return sprintf("%s %s -> %s", $this->startdate, $this->starttime, $this->enddate);
                } elseif ($this->startdate == $this->enddate) {
                        return sprintf("%s %s -> %s", $this->startdate, $this->starttime, $this->endtime);
                } else {
                        return sprintf("%s %s -> %s %s", $this->startdate, $this->starttime, $this->enddate, $this->endtime);
                }
        }

        /**
         * Render this component.
         */
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
                if (!isset($this->format)) {
                        $this->format = 'range';
                }

                if ($this->display == false) {
                        $this->display = 'none';
                }

                printf("<span class=\"%s\" style=\"%s;display:%s\">\n", $this->class, $this->style, $this->display);

                printf("<i class=\"fa fa-clock-o\"></i>\n ");
                if ($this->startdate == $this->enddate) {
                        printf("<span class=\"%s-start-date\">%s</span>\n", $this->prefix, $this->startdate);
                        printf("<span class=\"%s-start-time\">%s</span>\n", $this->prefix, $this->starttime);
                        printf("%s\n", self::ARROW);
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->endtime);
                } else {
                        printf("<span class=\"%s-start-date\">%s</span>\n", $this->prefix, $this->startdate);
                        printf("<span class=\"%s-start-time\">%s</span>\n", $this->prefix, $this->starttime);
                        printf("%s\n", self::ARROW);
                        printf("<span class=\"%s-end-date\">%s</span>\n", $this->prefix, $this->enddate);
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->endtime);
                }

                printf("</span>\n");
        }

}
