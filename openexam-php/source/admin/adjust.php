<?php

// 
// Copyright (C) 2010-2012 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/admin/adjust.php
// Author: Anders Lövgren
// Date:   2012-03-05
// 
// The admin page for adjusting answer scores.
//
// 
// Force logon for unauthenticated users:
// 
$GLOBALS['logon'] = true;

// 
// System check:
// 
if (!file_exists("../../conf/database.conf")) {
        header("location: setup.php?reason=database");
}
if (!file_exists("../../conf/config.inc")) {
        header("location: setup.php?reason=config");
}

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon and user interface support:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";
include "include/html.inc";

// 
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Business logic:
// 
include "include/admin.inc";
include "include/exam.inc";
include "include/teacher/correct.inc";
include "include/teacher/manager.inc";
include "include/scoreboard.inc";

// 
// The index page:
// 
class AdjustScoreAdminPage extends AdminPage
{

        private $params = array(
            "exam" => "/^\d+$/",
            "answer" => "/^\d+$/",
            "result" => "/^\d+$/",
            "score" => "/^\d+(\.\d)*$/",
            "comment" => "/^.*^/"
        );

        public function __construct()
        {
                parent::__construct(_("Adjust Scores"), $this->params);
        }

        //
        // The main entry point. This is where all processing begins.
        //
        public function printBody()
        {
                $this->assert("exam");

                if (!isset($this->param->answer)) {
                        $this->showScoreBoard();
                } elseif (!isset($this->param->score)) {
                        $this->showScoreForm();
                } else {
                        $this->assert(array("comment", "result"));
                        $this->saveScoreForm();
                }
        }

        // 
        // Show the form for modify the answer score.
        // 
        private function showScoreForm()
        {
                $correct = new Correct($this->param->exam);
                $answer = $correct->getQuestionAnswer($this->param->answer);

                $exam = new Exam();
                $question = $exam->getQuestionData($answer->getQuestionID());

                printf("<h3>" . _("Adjust the score for this question.") . "</h3>\n");

                printf("<p><b>%s:</b> %s (%d) [%s %0.1f]</p>\n", _("Question"), $question->getQuestionName(), $question->getQuestionID(), _("Max"), $question->getQuestionScore());

                $form = new Form("adjust.php", "POST");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("answer", $answer->getAnswerID());
                $form->addHidden("result", $answer->getResultID());
                $input = $form->addTextBox("score", sprintf("%0.1f", $answer->getResultScore()));
                $input->setLabel(_("Score"));
                $input = $form->addTextArea("comment", $answer->getResultComment());
                $input->setTitle(_("Add an comment describing the reason for modifying the score, including the requester and current date. This information will be visible for the student. "));
                $input->setRows(5);
                $input->setColumns(60);
                $input->setLabel(_("Comment"));
                $input = $form->addSubmitButton("submit", _("Submit"));
                $input->setLabel();
                $form->output();

                printf("<hr>\n");
                printf("<p><b>%s:</b> %s (%s)</p>\n", _("User"), $answer->getStudentUser(), $answer->getStudentCode());
        }

        // 
        // Save the posted answer score.
        // 
        private function saveScoreForm()
        {
                $correct = new Correct($this->param->exam);
                $s[$this->param->answer] = $this->param->score;
                $c[$this->param->answer] = $this->param->comment;
                $r[$this->param->answer] = $this->param->result;
                $correct->setAnswerResult($s, $c, $r);
                header(sprintf("Location: adjust.php?exam=%d", $this->param->exam));
        }

        // 
        // Show the admin score board.
        // 
        private function showScoreBoard()
        {
                printf("<h3>%s</h3>\n", _("Adjust Scores"));
                printf("<p>" .
                    _("This page allows scores to be adjusted even after an exam has been decoded. ") .
                    _("You should only set scores thru this page on direct demand from the exam creator.") .
                    "</p>\n");

                $board = new ScoreBoardAdmin($this->param->exam);
                $board->output();
        }

}

$page = new AdjustScoreAdminPage();
$page->render();
?>
