<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Check.php
// Created: 2017-02-28 17:50:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Gui\Component\Exam;

use OpenExam\Library\Core\Exam\Check as ExamStatusCheck;
use OpenExam\Library\Gui\Component;
use OpenExam\Models\Exam;

/**
 * Exam check status component.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Check extends ExamStatusCheck implements Component
{

        /**
         * Render as text label.
         */
        const RENDER_LABEL = 1;
        /**
         * Render as button.
         */
        const RENDER_BUTTON = 2;
        /**
         * Render as badge.
         */
        const RENDER_BADGE = 3;

        /**
         * The icon color.
         * @var string 
         */
        private $_color;
        /**
         * The text label.
         * @var string 
         */
        private $_text;
        /**
         * The task label.
         * @var string 
         */
        private $_task;
        /**
         * The icon name.
         * @var string 
         */
        private $_icon;
        /**
         * Render mode.
         * @var int
         */
        private $_mode = self::RENDER_LABEL;

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         */
        public function __construct($exam)
        {
                parent::__construct($exam);

                switch ($this->getStatus()) {
                        case self::STATUS_IS_READY:
                                $this->_text = false;
                                break;
                        case self::STATUS_NEED_ATTENTION:
                                $this->_text = 'Suggested task';
                                $this->_icon = 'exclamation';
                                $this->_color = 'blue';
                                break;
                        case self::STATUS_NOT_READY:
                                $this->_text = 'Remaining task';
                                $this->_icon = 'exclamation';
                                $this->_color = 'red';
                                break;
                }

                switch ($this->getRemainingTask()) {
                        case self::TASK_ADD_QUESTIONS:
                                $this->_task = 'Add questions';
                                break;
                        case self::TASK_ADD_STUDENTS:
                                $this->_task = 'Add students';
                                break;
                        case self::TASK_ALL_COMPLETED:
                                $this->_task = 'Exam is ready for use';
                                break;
                        case self::TASK_PUBLISH_EXAM:
                                $this->_task = 'Publish exam';
                                break;
                        case self::TASK_SET_NAME:
                                $this->_task = 'Set exam name';
                                break;
                        case self::TASK_SET_SECURITY:
                                if (($security = $this->getSecurity())) {
                                        if ($security == self::SECURITY_NONE) {
                                                $this->_task = 'Set exam security';
                                        } elseif (($security & self::SECURITY_LOCKDOWN) == 0) {
                                                $this->_task = 'Set exam lockdown';
                                        } elseif (($security & self::SECURITY_LOCATION) == 0) {
                                                $this->_task = 'Set exam location';
                                        }
                                }
                                break;
                        case self::TASK_SET_STARTTIME:
                                $this->_task = 'Set exam start time';
                                break;
                }
        }

        /**
         * Set render mode (one of RENDER_XXX constants).
         * @param int $mode The render mode.
         */
        public function setRenderMode($mode)
        {
                $this->_mode = $mode;
        }

        /**
         * Render this component.
         */
        public function render()
        {
                if (!$this->_text) {
                        return false;
                }

                switch ($this->_mode) {
                        case self::RENDER_LABEL:
                                printf("<i class=\"exam-status-check fa fa-2x fa-%s-circle\" style=\"color: %s\"></i>\n", $this->_icon, $this->_color);
                                printf("<span style=\"vertical-align: super\">%s: %s</span>\n", $this->_text, $this->_task);
                                break;
                        case self::RENDER_BADGE;
                                printf("<i class=\"exam-status-check fa fa-%s-circle\" style=\"color: %s\"></i>\n", $this->_icon, $this->_color);
                                printf("<span style=\"display: none\">%s: %s</span>\n", $this->_text, $this->_task);
                                break;
                        case self::RENDER_BUTTON;
                                printf("<span style=\"padding: 2px 10px 2px 10px;\" class=\"btn btn-default\">\n");
                                printf("<i class=\"exam-status-check fa fa-1x fa-%s-circle\" style=\"color: %s\"></i>\n", $this->_icon, $this->_color);
                                printf("<span style=\"display: none\">%s: %s</span>\n", $this->_text, $this->_task);
                                printf("</span>\n");
                                break;
                }
        }

}
