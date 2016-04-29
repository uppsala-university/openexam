<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportPingPong.php
// Created: 2015-04-15 00:12:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Questions;

use OpenExam\Library\Import\ImportBase;
use OpenExam\Library\Import\ImportData;

/**
 * Base class for import from Ping-Pong.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ImportPingPong extends ImportBase
{

        const FORMAT = "QI625";
        const EXPECT = "Exported from the questionbank in PING PONG";
        const XMLDOC = '<openexam/>';

        private $_questions = array();
        private $_question = null;
        private $_category = null;

        public function __construct($accept = "")
        {
                parent::__construct($accept);
                $this->_data = new ImportData(self::XMLDOC);
        }

        protected function append($key, $val)
        {
                printf("(key, val) = (%s, %s)\n", $key, $val);
                if ($key == "Question") {
                        if (isset($this->_question)) {
                                // 
                                // Map multiple choice with a single alternative to freetext.
                                // 
                                if (isset($this->_question['choice']) && count($this->_question['choice']) <= 1) {
                                        $this->_question['type'] = "freetext";
                                        unset($this->_question['choice']);
                                }

                                $this->_questions[$this->_category][] = $this->_question;
                        }
                        $this->_question = array("comment" => "", "score" => 1.0, "user" => $this->user->getPrincipalName());
                }
                if ($key == "Category") {
                        $this->_category = self::cleanup($val);
                }
                if ($key == "Name") {
                        $this->_question['name'] = self::cleanup($val);
                }
                if ($key == "Description") {
                        $this->_question['comment'] = self::cleanup($val);
                }
                if ($key == "Text") {
                        $this->_question['body'] = self::cleanup($val);
                }
                if ($key == "Max points") {
                        $this->_question['score'] = $val;
                }
                if ($key == "Type") {
                        if ($val == "Multiple choice") {
                                $this->_question['type'] = "multiple";
                                $this->_question['choice'] = array();
                        } elseif ($val == "Single choice") {
                                $this->_question['type'] = "single";
                                $this->_question['choice'] = array();
                        } elseif ($val == "Free writing") {
                                $this->_question['type'] = "freetext";
                        }
                }
                if ($key == "Correct") {
                        $this->_question['choice'][self::cleanup($val)] = true;
                }
                if ($key == "Incorrect") {
                        $this->_question['choice'][self::cleanup($val)] = false;
                }
                if ($key == "Marking guide") {   // TOOD: what to do with this?
                        $this->_question['guide'] = $val;
                }
        }

        // 
        // Prepare for insert.
        // 
        private function prepare(&$question, &$db)
        {
                $question['select'] = 0;
                if (isset($question['choice'])) {
                        foreach ($question['choice'] as $boolean) {
                                if ($boolean == true) {
                                        $question['select'] ++;
                                }
                        }
                }
                if ($question['select'] > 1) {
                        $question['type'] = 'multiple';
                } elseif ($question['select'] == 1) {
                        $question['type'] = 'single';
                } else {
                        $question['type'] = 'freetext';
                }
                if ($question['type'] == 'multiple' || $question['type'] == 'single') {
                        $question['quest'] = sprintf("%s\n\n%s", $question['body'], json_encode($question['choice']));
                        $question['quest'] = $db->escape($question['quest']);
                } else {
                        $question['quest'] = $db->escape($question['body']);
                }
        }

        public function read()
        {
                $tnode = $this->_data->addChild("topics");
                $qnode = $this->_data->addChild("questions");

                $tindex = 0;
                $qindex = 0;

                foreach ($this->_questions as $category => $questions) {
                        $child = $tnode->addChild("topic");
                        $child->addAttribute("id", ++$tindex);
                        $child->addChild("name", $category);
                        $child->addChild("random", 0);
                        foreach ($questions as $question) {
                                $this->prepare($question, $db);
                                $child = $qnode->addChild("question");
                                $child->addAttribute("id", ++$qindex);
                                $child->addAttribute("topic", $tindex);
                                $child->addChild("score", $question['score']);
                                $child->addChild("name", $question['name']);
                                $child->addChild("text", $question['quest']);
                                $child->addChild("publisher", $question['user']);
                                $child->addChild("type", $question['type']);
                                $child->addChild("comment", $question['comment']);
                        }
                }
        }

}
