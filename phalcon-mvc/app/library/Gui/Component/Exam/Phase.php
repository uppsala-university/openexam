<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Phase.php
// Created: 2016-10-27 15:54:48
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Gui\Component\Exam;

use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Gui\Component;

/**
 * Exam phase component.
 * 
 * @property string $pending The pending phase color.
 * @property string $active The active phase color.
 * @property string $completed The completed phase color.
 * @property string $missing The missing phase color.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Phase implements Component
{

        /**
         * The pending phase color.
         */
        const PENDING = '#33cc33';
        /**
         * The active phase color.
         */
        const ACTIVE = '#00ff00';
        /**
         * The completed phase color.
         */
        const COMPLETED = '#33cc33';
        /**
         * The missing phase color.
         */
        const MISSING = '#eeeeee';

        /**
         * The exam state.
         * @var State 
         */
        private $_state;

        /**
         * Constructor.
         * @param State $state The exam state object.
         */
        public function __construct($state)
        {
                $this->_state = $state;
        }

        public function render()
        {
                $output = function($color, $outline = false) {
                        if ($outline) {
                                printf("<i class=\"fa fa-circle-o\" style=\"color:%s\"></i>\n", $color);
                        } else {
                                printf("<i class=\"fa fa-circle\" style=\"color:%s\"></i>\n", $color);
                        }
                };

                if (!isset($this->pending)) {
                        $this->pending = self::PENDING;
                }
                if (!isset($this->active)) {
                        $this->active = self::ACTIVE;
                }
                if (!isset($this->completed)) {
                        $this->completed = self::COMPLETED;
                }
                if (!isset($this->missing)) {
                        $this->missing = self::MISSING;
                }

                // 
                // Time line phase:
                // 
                if ($this->_state->has(State::UPCOMING)) {
                        $output($this->pending, true);
                } elseif ($this->_state->has(State::RUNNING)) {
                        $output($this->active);
                } elseif ($this->_state->has(State::FINISHED)) {
                        $output($this->completed);
                } else {
                        for ($i = 0; $i < 4; ++$i) {
                                $output($this->missing, true);
                        }
                        return;
                }

                // 
                // The exam phase:
                // 
                if ($this->_state->has(State::ANSWERED)) {
                        $output($this->completed);
                } else {
                        $output($this->pending, true);
                }

                // 
                // The correction phase:
                // 
                if ($this->_state->has(State::CORRECTED)) {
                        $output($this->completed);
                } else {
                        $output($this->pending, true);
                }

                // 
                // The enquiry phase:
                // 
                if ($this->_state->has(State::ENQUIRY)) {
                        $output($this->completed);
                } else {
                        $output($this->pending, true);
                }

                // 
                // The decoding phase:
                // 
                if ($this->_state->has(State::DECODED)) {
                        $output($this->completed);
                } else {
                        $output($this->pending, true);
                }
        }

}
