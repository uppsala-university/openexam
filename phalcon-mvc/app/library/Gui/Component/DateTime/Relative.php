<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Relative.php
// Created: 2017-10-25 04:27:56
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Gui\Component\DateTime;

use OpenExam\Library\Gui\Component\DateTime;

/**
 * Relative datetime component.
 * 
 * <code>
 * $datetime = new DateTime\Relative('2017-10-24 11:45', '2017-10-24 14:00');
 * $datetime->render();         // 14:00
 * 
 * $datetime = new DateTime\Relative('2017-10-24 11:45', '2017-10-25 14:00');
 * $datetime->render();         // 2017-10-25 14:00
 * </code>
 *
 * @author Anders Lövgren (QNET)
 */
class Relative extends DateTime
{

        /**
         * Get datetime text.
         * @return string
         */
        public function text()
        {
                if ($this->_sdate == $this->_edate) {
                        return sprintf("%s", $this->_etime);
                } else {
                        return sprintf("%s %s", $this->_edate, $this->_etime);
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
                if ($this->display == false) {
                        $this->display = 'none';
                }

                printf("<span class=\"%s\" style=\"%s;display:%s\">\n", $this->class, $this->style, $this->display);

                if ($this->_sdate == $this->_edate) {
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->_etime);
                } else {
                        printf("<span class=\"%s-end-date\">%s</span>\n", $this->prefix, $this->_edate);
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->_etime);
                }

                printf("</span>\n");
        }

}
