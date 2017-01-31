<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
