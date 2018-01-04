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
                if (!$this->_etime) {
                        return "";
                } elseif ($this->startdate == $this->enddate) {
                        return sprintf("%s", $this->endtime);
                } else {
                        return sprintf("%s %s", $this->enddate, $this->endtime);
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

                if ($this->startdate == $this->enddate) {
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->endtime);
                } else {
                        printf("<span class=\"%s-end-date\">%s</span>\n", $this->prefix, $this->enddate);
                        printf("<span class=\"%s-end-time\">%s</span>\n", $this->prefix, $this->endtime);
                }

                printf("</span>\n");
        }

}
