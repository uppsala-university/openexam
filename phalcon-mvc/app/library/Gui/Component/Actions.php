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
// File:    Actions.php
// Created: 2016-10-28 02:14:15
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Library\Gui\Component;

use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Gui\Component;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use Phalcon\Mvc\User\Component as PhalconComponent;

/**
 * Actions buttons component.
 * 
 * This class represent the possible actions (as UI buttons) for an exam 
 * given the requested role.
 * 
 * @property-write Exam $exam The exam model.
 * @property-write string $role The requested role.
 * @property-read Button[] $buttons The array of action buttons.
 * 
 * @see Roles
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Actions extends PhalconComponent implements Component
{

        /**
         * The exam model.
         * @var Exam 
         */
        private $_exam;
        /**
         * The requested role.
         * @var string 
         */
        private $_role;
        /**
         * The button array.
         * @var Button[] 
         */
        private $_buttons;

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         * @param string $role The requested role.
         */
        public function __construct($exam, $role)
        {
                $this->_exam = $exam;
                $this->_role = $role;
        }

        public function __set($name, $value)
        {
                if ($name == 'exam') {
                        $this->exam = $value;
                        $this->_buttons = null;
                }
                if ($name == 'role') {
                        $this->role = $value;
                        $this->_buttons = null;
                }
        }

        public function __get($name)
        {
                if (!isset($this->_buttons)) {
                        $this->prepare();
                }
                if ($name == 'buttons') {
                        return $this->_buttons;
                }
        }

        public function render()
        {
                if (!isset($this->_buttons)) {
                        $this->prepare();
                }
        }

        /**
         * Populate buttons array.
         */
        private function prepare()
        {
                $this->_buttons = array();

                if (!$this->user->roles->acquire($this->_role, $this->_exam->id)) {
                        return;
                }

                $state = $this->_exam->getState();

                if ($this->_role == Roles::STUDENT) {
                        if ($state->has(State::UPCOMING) ||
                            $state->has(State::RUNNING)) {
                                $this->append(array(
                                        'tag'    => 'st-exam-page',
                                        'text'   => $this->tr('Open exam'),
                                        'icon'   => 'gear',
                                        'color'  => 'green',
                                        'target' => $this->url->get(
                                            sprintf(
                                                '/exam/%d', $this->exam->id
                                            )
                                        )
                                ));
                        }
                } elseif ($this->_role == Roles::CREATOR) {
                        
                } elseif ($this->_role == Roles::CONTRIBUTOR) {
                        if ($state->has(State::CONTRIBUTABLE)) {
                                $this->append(array(
                                        'tag'    => 'add-q',
                                        'text'   => $this->tr('Add questions'),
                                        'icon'   => 'pencil-square-o',
                                        'color'  => 'blue',
                                        'target' => $this->url->get(
                                            sprintf(
                                                '/exam/update/%d/contributor/add-q', $this->exam->id
                                            )
                                        )
                                ));
                        }
                        $this->append(array(
                                'tag'    => 'view-exam',
                                'text'   => $this->tr('View exam'),
                                'icon'   => 'question-circle',
                                'color'  => 'green',
                                'target' => $this->url->get(
                                    sprintf(
                                        '/exam/update/%d/contributor', $this->exam->id
                                    )
                                )
                        ));
                } elseif ($this->_role == Roles::CORRECTOR) {
                        
                } elseif ($this->_role == Roles::DECODER) {
                        
                } elseif ($this->_role == Roles::INVIGILATOR) {
                        
                }
        }

        /**
         * Insert a button in array.
         * @param array $params The button options.
         */
        private function append($params)
        {
                $button = new Button();

                if (isset($params['target'])) {
                        $button->target = $params['target'];
                }
                if (isset($params['text'])) {
                        $button->text = $params['text'];
                }
                if (isset($params['icon'])) {
                        $button->icon = $params['icon'];
                }
                if (isset($params['color'])) {
                        $button->color = $params['color'];
                }
                if (isset($params['outline'])) {
                        $button->outline = $params['outline'];
                }
                if (isset($params['tag'])) {
                        $button->tag = $params['tag'];
                }

                $this->_buttons[] = $button;
        }

}
