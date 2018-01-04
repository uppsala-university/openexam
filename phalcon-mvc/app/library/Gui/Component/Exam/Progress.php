<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Progress.php
// Created: 2016-11-29 20:34:41
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Gui\Component\Exam;

use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Gui\Component;
use Phalcon\Mvc\User\Component as PhalconComponent;

/**
 * Exam progress component.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Progress extends PhalconComponent implements Component
{

        /**
         * The exam ID.
         * @var int 
         */
        private $_exam;
        /**
         * The role to render for.
         * @var string 
         */
        private $_role;
        /**
         * The exam state.
         * @var State 
         */
        private $_state;
        /**
         * The CSS style.
         * @var string 
         */
        private $_style;

        /**
         * Constructor.
         * @param int $exam The exam ID.
         * @param string $role Render component for role.
         * @param State $state The exam state object.
         */
        public function __construct($exam, $role, $state)
        {
                $this->_exam = $exam;
                $this->_role = $role;

                $this->_state = $state;
                $this->_style = "display: inline-block; text-align: left";

                if (isset($this->_role)) {
                        if (!$this->user->roles->acquire($this->_role)) {
                                $this->_role = null;
                        }
                }
        }

        public function render()
        {
                $output = function($color, $icon, $class, $outline = false, $text = "") {
                        if (isset($this->_role)) {
                                printf("<a data-id=\"%d\" href=\"#\" class=\"exam-progress %s %s prevent\">\n", $this->_exam, $this->_role, $class);
                        }

                        if ($outline) {
                                printf("<span class=\"toolbtn toolbtn-%s-outline\" style=\"%s\"><i class=\"fa fa-%s\"></i> %s</span>\n", $color, $this->_style, $icon, $text);
                        } else {
                                printf("<span class=\"toolbtn toolbtn-%s\" style=\"%s\"><i class=\"fa fa-%s\"></i> %s</span>\n", $color, $this->_style, $icon, $text);
                        }

                        if (isset($this->_role)) {
                                printf("</a>\n");
                        }
                };

                // 
                // Check from backward:
                // 
                if ($this->_state->has(State::DECODED)) {
                        $output("grey", "battery-4", "decoded", false, $this->tr->_("Decoded"));
                } elseif ($this->_state->has(State::ENQUIRY)) {
                        $output("red", "battery-4", "enquiry", true, $this->tr->_("Enquiry"));
                } elseif ($this->_state->has(State::CORRECTED)) {
                        $output("orange", "battery-3", "corrected", true, $this->tr->_("Corrected"));
                } elseif ($this->_state->has(State::FINISHED)) {
                        $output("orange", "battery-2", "finished", true, $this->tr->_("Finished"));
                } elseif ($this->_state->has(State::ANSWERED)) {
                        $output("light-green", "battery-1", "answered", false, $this->tr->_("Answered"));
                } elseif ($this->_state->has(State::RUNNING) && $this->_state->has(State::PUBLISHED) == true) {
                        $output("light-green", "battery-0", "published", false, $this->tr->_("Ongoing"));
                } elseif ($this->_state->has(State::RUNNING) && $this->_state->has(State::PUBLISHED) == false) {
                        $output("red", "battery-0", "upcoming", true, $this->tr->_("Started"));
                } elseif ($this->_state->has(State::PUBLISHED)) {
                        $output("blue", "battery-0", "published", true, $this->tr->_("Published"));
                } elseif ($this->_state->has(State::UPCOMING)) {
                        $output("blue", "battery-0", "upcoming", true, $this->tr->_("Upcoming"));
                }
        }

}
