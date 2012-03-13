<?php

// 
// Copyright (C) 2010-2012 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/correct.php
// Author: Anders Lövgren
// Date:   2010-04-28
// 
// This page is used by teachers for correcting answers to questions on 
// an exam.
//
// 
// Force logon for unauthenticated users:
// 
$GLOBALS['logon'] = true;

// 
// System check:
// 
if (!file_exists("../../conf/database.conf")) {
        header("location: ../admin/setup.php?reason=database");
}
if (!file_exists("../../conf/config.inc")) {
        header("location: ../admin/setup.php?reason=config");
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
include "include/teacher.inc";
include "include/exam.inc";
include "include/teacher/manager.inc";
include "include/teacher/correct.inc";

// 
// Support classes:
// 
include "include/scoreboard.inc";

// 
// The answer correction page:
// 
class CorrectionPage extends TeacherPage
{

        private static $params = array(
                "exam"     => parent::pattern_index,
                "answer"   => parent::pattern_index,
                "result"   => parent::pattern_index,
                "question" => parent::pattern_index,
                "student"  => parent::pattern_index,
                "verbose"  => parent::pattern_index,
                "colorize" => parent::pattern_index,
                "score"    => parent::pattern_float,
                "comment"  => parent::pattern_text,
                "mode"     => "/^(mark|save)$/"
        );

        public function __construct()
        {
                $this->param->verbose = false;
                $this->param->colorize = false;

                parent::__construct(_("Answer Correction Page"), self::$params);
        }

        //
        // The main entry point. This is where all processing begins.
        //
        public function printBody()
        {
                //
                // Authorization first:
                //
                if (isset($this->param->exam)) {
                        $this->checkAccess();
                }

                //
                // Bussiness logic:
                //
                if (isset($this->param->exam)) {
                        if (isset($this->param->question)) {
                                if (isset($this->param->mode) && $this->param->mode == "save") {
                                        $this->assert(array('score', 'comment'));
                                        $this->saveQuestionScore();
                                } else {
                                        $this->markQuestionScore();
                                }
                        } elseif (isset($this->param->student)) {
                                if (isset($this->param->mode) && $this->param->mode == "save") {
                                        $this->assert(array('score', 'comment'));
                                        $this->saveStudentScore();
                                } else {
                                        $this->markStudentScore();
                                }
                        } elseif (isset($this->param->answer)) {
                                if (isset($this->param->mode) && $this->param->mode == "save") {
                                        $this->assert(array('score', 'comment'));
                                        $this->saveAnswerScore();
                                } else {
                                        $this->markAnswerScore();
                                }
                        } else {
                                if (isset($this->param->mode) && $this->param->mode == "save") {
                                        $this->saveScoreBoard();
                                } else {
                                        $this->showScoreBoard();
                                }
                        }
                } else {
                        self::showAvailableExams();
                }
        }

        //
        // verify that the caller has been granted the required role on this exam.
        //
        private function checkAccess()
        {
                $role1 = "contributor";
                $role2 = "corrector";

                if (!$this->manager->hasRole(phpCAS::getUser(), $role1) &&
                    !$this->manager->hasRole(phpCAS::getUser(), $role2)) {
                        $this->fatal(_("Access denied!"), sprintf(_("Only users granted the %s or %s role on this exam can access this page. The script processing has halted."), $role1, $role2));
                }
                if (!$this->manager->getInfo()->isFinished()) {
                        $this->fatal(_("Access denied!"), _("This examination is not yet finished. You have to wait until its finished before you can correct the answers."));
                }
        }

        //
        // Save result from saveQuestionScore(), saveStudentScore() and saveAnswerScore().
        //
        private function saveAnswerResult()
        {
                $results = isset($this->param->result) ? $this->param->result : array();
                $correct = new Correct($this->param->exam);
                $correct->setAnswerResult($this->param->score, $this->param->comment, $results);
                header(sprintf("location: correct.php?exam=%d", $this->param->exam));
        }

        //
        // Save result posted from saveAnswerScore().
        //
        private function saveAnswerScore()
        {
                $this->saveAnswerResult();
        }

        //
        // Save result posted from markStudentScore().
        //
        private function saveStudentScore()
        {
                $this->saveAnswerResult();
        }

        //
        // Save result from markQuestionScore().
        //
        private function saveQuestionScore()
        {
                $this->saveAnswerResult();
        }

        //
        // Display the answer to a single question.
        //
        private function viewQuestionAnswer($question, $answer, &$table, &$form)
        {
                $row = $table->addRow();
                $row->setClass("nonth");
                $row->addData();

                if ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        $row = $table->addRow();
                        $row->setClass("question");
                        $row->addData(sprintf("<u>%s: %s</u><br />%s", _("Question"), $question->getQuestionName(), str_replace("\n", "<br/>", $question->getQuestionText())));
                } else {
                        $qchoice = Exam::getQuestionChoice($question->getQuestionText(), true);
                        $row = $table->addRow();
                        $row->setClass("question");
                        $row->addData(sprintf("<u>%s: %s</u><br />%s<br/><br/>%s: %s", _("Question"), $question->getQuestionName(), str_replace("\n", "<br/>", $qchoice[0]), _("Correct answer"), implode(", ", array_keys($qchoice[1], true))));
                }

                if ($question->getQuestionStatus() == 'removed') {
                        $row = $table->addRow();
                        $data = $row->addData(_("This question is flagged as removed. The answer score set here will not affect the student result on this examination."));
                        $data->setClass("removed");
                        $data->addElement(new Image("/openexam/icons/info.png", _("Info icon")));
                }

                $row = $table->addRow();
                $row->setClass("answer");
                if ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        $row->addData(sprintf("<u>%s</u>:<br />%s", _("Answer"), str_replace("\n", "<br/>", htmlspecialchars($answer->getAnswerText()))));
                } else {
                        $achoice = Exam::getQuestionChoice($answer->getAnswerText());
                        $row->addData(sprintf("<u>%s</u>:<br />%s", _("Answer"), str_replace("\n", "<br/>", implode(", ", $achoice[1]))));
                }
                if ($answer->hasResultID()) {
                        $form->addHidden(sprintf("result[%d]", $answer->getAnswerID()), $answer->getResultID());
                }
                if ($answer->hasResultScore()) {
                        $data = $row->addData(sprintf("<br/>%s: %.01f", _("Max score"), $question->getQuestionScore()));
                        $data->setValign(TABLE_VALIGN_TOP);
                        $textbox = $data->addTextBox(sprintf("score[%d]", $answer->getAnswerID()), sprintf("%.01f", $answer->getResultScore()));
                        $textbox->setSize(8);
                        $textbox->setEvent(EVENT_ON_FOCUS, "javascript:start_check(this);");
                        $textbox->setEvent(EVENT_ON_BLUR, sprintf("javascript:check_range(this, 0, %f);", $question->getQuestionScore()));
                } elseif ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        $data = $row->addData(sprintf("<br/>%s: %.01f", _("Max score"), $question->getQuestionScore()));
                        $data->setValign(TABLE_VALIGN_TOP);
                        $textbox = $data->addTextBox(sprintf("score[%d]", $answer->getAnswerID()), 0.0);
                        $textbox->setSize(8);
                        $textbox->setEvent(EVENT_ON_FOCUS, "javascript:start_check(this);");
                        $textbox->setEvent(EVENT_ON_BLUR, sprintf("javascript:check_range(this, 0, %f);", $question->getQuestionScore()));
                } else {
                        //
                        // Compare student answers against the correct answers
                        //
                        $keys = array_keys($qchoice[1], true);
                        $hits = 0;
                        foreach ($keys as $key) {
                                if (in_array($key, $achoice[1])) {
                                        $hits++;   // Increment on correct answer
                                }
                        }
                        foreach ($achoice[1] as $key) {
                                if (!in_array($key, $keys)) {
                                        $hits--;   // Substract for wrong answer
                                }
                        }
                        if ($hits < 0) {
                                $hits = 0;
                        }
                        $data = $row->addData(sprintf("<br/>%s: %.01f", _("Max score"), $question->getQuestionScore()));
                        $data->setValign(TABLE_VALIGN_TOP);
                        $textbox = $data->addTextBox(sprintf("score[%d]", $answer->getAnswerID()), sprintf("%.01f", ($hits / count($keys)) * $question->getQuestionScore()));
                        $textbox->setSize(8);
                        $textbox->setEvent(EVENT_ON_FOCUS, "javascript:start_check(this);");
                        $textbox->setEvent(EVENT_ON_BLUR, sprintf("javascript:check_range(this, 0, %f);", $question->getQuestionScore()));
                }
                $row = $table->addRow();
                $row->setClass("comment");
                $data = $row->addData("(" . _("Comment") . ")");
                $textarea = $data->addElement(
                    new TextArea(sprintf("comment[%d]", $answer->getAnswerID()),
                        $answer->hasResultComment() ? $answer->getResultComment() : ""));
                $textarea->setTitle(_("This optional field can be used to save an comment for this answer correction."));
                $textarea->setRows(2);
                $textarea->setColumns(90);
        }

        //
        // Examine (correct) an answer to a single question from this student.
        //
        private function markAnswerScore()
        {
                $correct = new Correct($this->param->exam);
                $answer = $correct->getQuestionAnswer($this->param->answer);

                if ($answer->getQuestionPublisher() != phpCAS::getUser()) {
                        $this->fatal(_("Access denied!"), sprintf(_("Correction of answers to this question has been assigned to %s, you are not allowed to continue. The script processing has halted."), $this->getFormatName($answer->getQuestionPublisher())));
                }

                $exam = new Exam();
                $question = $exam->getQuestionData($answer->getQuestionID());

                printf("<h3>" . _("Correct the answer for this question.") . "</h3>\n");
                $form = new Form("correct.php", "POST");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("action", "correct");
                $form->addHidden("answer", $this->param->answer);
                $form->addHidden("mode", "save");
                $table = new Table();
                $row = $table->addRow();
                $row->addHeader(_("Answer"));
                $row->addHeader(_("Score"));
                $this->viewQuestionAnswer($question, $answer, $table, $form);
                $form->addElement($table);
                $form->addSpace();
                $form->addSubmitButton("submit", _("Submit"));
                $form->output();
        }

        //
        // Examine all answers from a single student.
        //
        private function markStudentScore()
        {
                $correct = new Correct($this->param->exam);
                $answers = $correct->getStudentAnswers($this->param->student);

                $exam = new Exam();

                printf("<h3>" . _("Correct all answers at once for this student examination") . "</h3>\n");
                printf("<p>" . _("Only those questions where this student have given an answer for is shown below. Questions published by other people for this examination is hidden.") . "</p>\n");

                if ($answers->count() == 0) {
                        $mbox = new MessageBox();
                        $mbox->setTitle(_("No Answers Found"));
                        $mbox->setMessage(_("It appears that this student have not answered any questions at all."));
                        $mbox->display();
                        return;
                }

                //
                // Show removed questions, but only in verbose mode.
                //
                $found->answers = 0;
                $found->removed = 0;
                foreach ($answers as $answer) {
                        if ($answer->getQuestionPublisher() == phpCAS::getUser()) {
                                $found->answers++;
                                if ($answer->getQuestionStatus() == 'removed') {
                                        $found->removed++;
                                }
                        }
                }
                if ($found->answers == 0) {
                        $mbox = new MessageBox();
                        $mbox->setTitle(_("No Answers Found"));
                        $mbox->setMessage(_("It appears that this student have not answered any of your questions."));
                        $mbox->display();
                        return;
                }
                if ($found->removed > 0) {
                        printf("<span class=\"links viewmode\">");
                        printf("%s: <a href=\"?exam=%d&amp;action=correct&amp;student=%d&amp;verbose=%d\">%s</a>", _("Show"), $this->param->exam, $this->param->student, $this->param->verbose == false, $this->param->verbose ? _("Answered") : _("All"));
                        printf("</span>\n");
                }
                if ($found->answers - $found->removed == 0 && $this->param->verbose == false) {
                        $link = sprintf("?exam=%d&amp;action=correct&amp;student=%d&amp;verbose=1", $this->param->exam, $this->param->student);
                        $mbox = new MessageBox();
                        $mbox->setTitle(_("No Answers Found"));
                        $mbox->setMessage(sprintf(_("Only answers to removed questions where found. Click <a href=\"%s\">here</a> to view answers for those questions."), $link));
                        $mbox->display();
                        return;
                }

                $form = new Form("correct.php", "POST");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("action", "correct");
                $form->addHidden("student", $this->param->student);
                $form->addHidden("mode", "save");
                $table = new Table();
                $row = $table->addRow();
                $row->addHeader(_("Answer"));
                $row->addHeader(_("Score"));
                foreach ($answers as $answer) {
                        if ($answer->getQuestionPublisher() != phpCAS::getUser()) {
                                continue;   // Not publisher of this question.
                        }
                        $question = $exam->getQuestionData($answer->getQuestionID());
                        $this->viewQuestionAnswer($question, $answer, $table, $form);
                }
                $form->addElement($table);
                $form->addSpace();
                $form->addSubmitButton("submit", _("Submit"));
                $form->output();
        }

        //
        // Display the form where caller can set scores and comments for all answers
        // at once to a single question.
        //
        private function markQuestionScore()
        {
                $correct = new Correct($this->param->exam);
                $answers = $correct->getQuestionAnswers($this->param->question);

                $exam = new Exam();
                $question = $exam->getQuestionData($this->param->question);

                printf("<h3>" . _("Correct multipe answers for the question '%s'") . "</h3>\n", $question->getQuestionName());
                if ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        printf("<p><u>%s</u>:</p><p>%s</p>", _("Question"), str_replace("\n", "<br/>", $question->getQuestionText()));
                } else {
                        $qchoice = Exam::getQuestionChoice($question->getQuestionText(), true);
                        printf("<p><u>%s</u>:</p><p>%s</p><p>%s: %s<br />%s: %s</p>", _("Question"), str_replace("\n", "<br/>", $qchoice[0]), _("Choices"), implode(", ", array_keys($qchoice[1])), _("Correct answer"), implode(", ", array_keys($qchoice[1], true)));
                }
                printf("<p><u>%s</u>: %.01f</p>", _("Max score"), $question->getQuestionScore());

                if ($answers->count() == 0) {
                        $mbox = new MessageBox();
                        $mbox->setTitle(_("No Answers Found"));
                        $mbox->setMessage(_("It appears that no students have answered this question."));
                        $mbox->display();
                        return;
                }

                $form = new Form("correct.php", "POST");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("action", "correct");
                $form->addHidden("question", $this->param->question);
                $form->addHidden("mode", "save");
                $table = new Table();
                $row = $table->addRow();
                $row->addHeader(_("Answer"));
                $row->addHeader(_("Score"));
                foreach ($answers as $answer) {
                        $this->viewQuestionAnswer($question, $answer, $table, $form);
                }
                $form->addElement($table);
                $form->addSpace();
                $form->addSubmitButton("submit", _("Submit"));
                $form->output();
        }

        private static function showAvailableExams()
        {
                printf("<h3>" . _("Correct Answers") . "</h3>\n");
                printf("<p>" .
                    _("Select the examination you wish to correct answers to questions for (applies only to corractable examinations). ") .
                    _("You can also follow the link to review an already decoded examination.") .
                    "</p>\n");

                $tree = new TreeBuilder(_("Examinations"));
                $root = $tree->getRoot();

                //
                // Group the examinations by their state:
                //
                $exams = Correct::getExams(phpCAS::getUser());
                $nodes = array(
                        'c' => array(
                                'name' => _("Correctable"),
                                'data' => array()
                        ),
                        'd' => array(
                                'name' => _("Decoded"),
                                'data' => array()
                        ),
                        'u' => array(
                                'name' => _("Upcoming"),
                                'data' => array()
                        ),
                        'a' => array(
                                'name' => _("Active"),
                                'data' => array()
                        )
                );

                foreach ($exams as $exam) {
                        $manager = new Manager($exam->getExamID());
                        $state = $manager->getInfo();
                        if ($state->isUpcoming()) {
                                $nodes['u']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isCorrectable()) {
                                $nodes['c']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isDecoded()) {
                                $nodes['d']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isRunning()) {
                                $nodes['a']['data'][] = array($exam->getExamName(), $state);
                        }
                }

                foreach ($nodes as $type => $group) {
                        if (count($group['data']) > 0) {
                                $node = $root->addChild($group['name']);
                                foreach ($group['data'] as $data) {
                                        $name = $data[0];
                                        $state = $data[1];
                                        $child = $node->addChild($name);
                                        if ($state->isCorrectable()) {
                                                $child->setLink(sprintf("?exam=%d", $state->getInfo()->getExamID()), _("Click on this link to open this examination to correct answers."));
                                        } elseif ($state->isDecoded()) {
                                                $child->setLink(sprintf("?exam=%d", $state->getInfo()->getExamID()), _("Click on this link to review this examination."));
                                        }
                                        $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamStartTime()))));
                                        $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamEndTime()))));
                                }
                        }
                }

                $tree->output();
        }

        private function showScoreBoard()
        {
                $data = $this->manager->getData();

                //
                // Output ingress:
                //
                if ($data->getExamDecoded() == 'N') {
                        printf("<h3>" . _("Correct Answers") . "</h3>\n");
                        printf("<p>" .
                            _("This table shows all answers from students to questions for the examination '%s'. ") .
                            "</p>\n", $data->getExamName());
                        printf("<p>" .
                            _("Correct answers by student (rows), by question (column) or individual (by index). ") .
                            _("You can only correct answers for questions published by yourself or those you have been assigned the role as corrector of.") .
                            "</p>\n");
                } else {
                        printf("<h3>" . _("Showing Scores") . "</h3>\n");
                        printf("<p>" .
                            _("This table shows all answers from students to questions for the examination '%s'. ") .
                            "</p>\n", $data->getExamName());
                        printf("<p>" .
                            _("The examination has already been decoded, so it's no longer possible to modify any scores or comments.") .
                            "</p>\n");
                }

                //
                // Links for customize output:
                //
                printf("<span class=\"links viewmode\">");
                printf("%s: <a href=\"?exam=%d&amp;verbose=%d\">%s</a>, ", _("Details"), $this->param->exam, $this->param->verbose == false, $this->param->verbose ? _("Less") : _("More"));
                printf("%s: <a href=\"?exam=%d&amp;colorize=%d\">%s</a>", _("Mode"), $this->param->exam, $this->param->colorize == false, $this->param->colorize ? _("Standard") : _("Colorize"));
                printf("</span>\n");

                //
                // Output the score board using selected options:
                //
                $board = new ScoreBoardPrinter($this->param->exam);
                $board->setVerbose($this->param->verbose);
                $board->setColorized($this->param->colorize);
                $board->output();

                //
                // The color codes table:
                //
                printf("<h5>" . _("Color Codes") . "</h5>\n");
                printf("<p>" . _("These are the color codes used in the score board:") . "</p>\n");
                if ($this->param->colorize) {
                        $codes = array(
                                "s0"   => sprintf(_("Less than %d%% of max score."), 20),
                                "s20"  => sprintf(_("Between %d and %d %% of max score."), 20, 40),
                                "s40"  => sprintf(_("Between %d and %d %% of max score."), 40, 60),
                                "s60"  => sprintf(_("Between %d and %d %% of max score."), 60, 80),
                                "s80"  => sprintf(_("Between %d and %d %% of max score."), 80, 99),
                                "s100" => sprintf(_("%d%% correct answer (full score)."), 100));
                } else {
                        $codes = array(
                                "ac"   => _("Answer has been corrected."),
                                "no"   => _("This answer should be corrected by another person."),
                                "na"   => _("No answer was given for this question."),
                                "nc"   => _("The answer has not yet been corrected."),
                                "qr"   => _("Question is flagged as removed (no scores for this question is counted)."),
                                "qu"   => _("This question was not assigned to this student.")
                        );
                }
                $table = new Table();
                foreach ($codes as $code => $desc) {
                        $row = $table->addRow();
                        $row->setClass("colorcode");
                        $data = $row->addData();
                        $data->setClass(sprintf("cc %s", $code));
                        $data = $row->addData($desc);
                }
                $table->output();

                //
                // Download should either be removed or provide the full spectra of
                // formats using class ScoreBoardWriter (see decoded.php).
                //
                printf("<h5>" . _("Download Result") . "</h5>\n");
                printf("<p>" . _("Click <a href=\"%s\">here</a> to download the score board.") . "</p>\n", sprintf("?exam=%d&amp;mode=save", $this->param->exam));
        }

        private function saveScoreBoard()
        {
                $data = $this->manager->getData();

                $board = new ScoreBoard($this->param->exam);
                $questions = $board->getQuestions();

                ob_end_clean();

                header("Content-Type: text/tab-separated-values");
                header(sprintf("Content-Disposition: attachment;filename=\"%s.tab\"", str_replace(" ", "_", $data->getExamName())));
                header("Cache-Control: no-cache");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: no-cache");
                header("Expires: 0");

                $i = 1;
                printf("%s", _("Code"));
                foreach ($questions as $question) {
                        printf("\tQ%d.", $i++);
                }
                printf("\t%s", _("Score"));
                printf("\t%s", _("Possible"));
                printf("\t%s", _("Max score"));
                printf("\t%s", _("Percent"));
                printf("\t%s\n", _("Grade"));
                //
                // Output the list of anonymous students.
                //
                $students = $board->getStudents();
                $grades = new ExamGrades($data->getExamGrades());
                foreach ($students as $student) {
                        printf("%s", $student->getStudentCode());
                        foreach ($questions as $question) {
                                $data = $board->getData($student->getStudentID(), $question->getQuestionID());
                                if (!isset($data)) {
                                        printf("\t");
                                } else {
                                        if ($data->hasResultScore()) {
                                                printf("\t%.01f", $data->getResultScore());
                                        } else {
                                                printf("\t");
                                        }
                                }
                        }
                        $score = $board->getStudentScore($student->getStudentID());
                        $grade = $grades->getGrade($score->getSum());
                        printf("\t%.01f", $score->getSum());
                        printf("\t%.01f", $score->getMax());
                        printf("\t%.01f", $board->getMaximumScore());
                        printf("\t%.01f", 100 * $score->getSum() / $board->getMaximumScore());
                        printf("\t%s", $grade);
                        printf("\n");
                }
                exit(0);
        }

}

$page = new CorrectionPage();
$page->render();
?>
