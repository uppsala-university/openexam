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
                if ($this->_sdate == $this->_edate) {
                        return sprintf("%s %s -> %s", $this->_sdate, $this->_stime, $this->_etime);
                } else {
                        return sprintf("%s %s -> %s", $this->_sdate, $this->_stime, $this->_edate, $this->_etime);
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
                if ($this->_sdate == $this->_edate) {
                        printf("<span class=\"%s-start-date\">%s</span>\n", $this->prefix, $this->_sdate);
                        printf("<span class=\"%s-start-time\">%s</span>\n", $this->prefix, $this->_stime);
                        printf("%s\n", self::ARROW);
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->_etime);
                } else {
                        printf("<span class=\"%s-start-date\">%s</span>\n", $this->prefix, $this->_sdate);
                        printf("<span class=\"%s-start-time\">%s</span>\n", $this->prefix, $this->_stime);
                        printf("%s\n", self::ARROW);
                        printf("<span class=\"%s-end-date\">%s</span>\n", $this->prefix, $this->_edate);
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->_etime);
                }

                printf("</span>\n");
        }

}
