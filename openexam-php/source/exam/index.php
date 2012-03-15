<?php

// 
// Copyright (C) 2010-2012 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/exam/index.php
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// This is the page where students do their exam.
//
// 
// Enable autosave if non-zero. Try to set session length twice the value of
// the autosave interval. This should affect the CAS logon session length.
// 
if (!defined("SESSION_AUTOSAVE")) {
        define("SESSION_AUTOSAVE", 0);
}
if (!defined("SESSION_LIFETIME")) {
        define("SESSION_LIFETIME", 2 * SESSION_AUTOSAVE);
}

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
// If logon is true, then CAS logon is enforced for this page.
// 
$GLOBALS['logon'] = true;

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon, user interface and support for error reporting:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";
include "include/html.inc";
include "include/handler/handler.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include bussiness logic:
// 
include "include/exam.inc";
include "include/mplayer.inc";
include "include/locker.inc";

// 
// Needed to bypass access checks for contributors (in preview mode):
// 
include "include/teacher/manager.inc";

// 
// This class implements a standard page.
// 
class ExaminationPage extends BasePage
{

        //
        // All possible request parameters should be added here along with
        // the regex pattern to validate its value against.
        //
        private static $params = array(
                "exam"     => parent::pattern_index,
                "answer"   => parent::pattern_text,
                "question" => "/^(\d+|all)$/",
                "status"   => "/^(ok)$/",
                "save"     => parent::pattern_text, // button
                "next"     => parent::pattern_text  // button
        );
        private $author = false;    // Running in question author mode.
        private $lockdown = false;  // This examination has lockdown mode enabled.
        private $testcase = false;  // This examination is a testcase.
        //
        // Construct the exam page.

        //

        public function __construct()
        {
                parent::__construct(_("Examination:"), self::$params);   // Internationalized with GNU gettext
        }

        //
        // The template page body.
        //
        public function printBody()
        {
                //
                // Authorization first:
                //
                if (isset($this->param->exam)) {
                        $this->checkExaminationAccess();
                        if (isset($this->param->question) && $this->param->question != "all") {
                                $this->checkQuestionAccess();
                        }
                }

                //
                // Bussiness logic:
                //
                if (!isset($this->param->exam)) {
                        $this->showAvailableExams();
                } else {
                        if (!isset($this->param->question)) {
                                $this->showInstructions();
                        } elseif ($this->param->question == "all") {
                                $this->showQuestions();
                        } elseif (isset($this->param->answer)) {
                                $this->saveQuestion();
                        } else {
                                $this->showQuestion();
                        }
                }

                //
                // This block is only relevant if running the exam is test mode.
                //
                if ($this->testcase) {
                        printf("<hr/>\n");
                        printf("<b>" . ("Test case") . ":</b> <a href=\"%s\" title=\"%s\">%s</a> <a href=\"%s\" title=\"%s\">%s</a>\n", sprintf("../teacher/manager.php?exam=%d&amp;action=finish", $this->param->exam), _("Stops the examination and allow you to correct and decode results."), _("Finish"), sprintf("../teacher/manager.php?exam=%d&amp;action=cancel", $this->param->exam), _("Delete this test case and return to the examination manager."), _("Cancel"));
                }
        }

        public function printMenu()
        {

                if (isset($this->param->exam) && !isset($this->param->preview)) {
                        $exams = Exam::getActiveExams(phpCAS::getUser());
                        if ($exams->count() > 1) {
                                echo "<span id=\"menuhead\">" . _("Examinations") . ":</span>\n";
                                echo "<ul>\n";
                                foreach ($exams as $exam) {
                                        printf("<li><a href=\"?exam=%d\" title=\"%s\">%s</a></li>\n", $exam->getExamID(), $exam->getExamDescription(), $exam->getExamName());
                                }
                                echo "</ul>\n";
                        }

                        $menuitem = self::getQuestions();

                        if (isset($menuitem['q'])) {
                                echo "<span id=\"menuhead\">" . _("Questions:") . "</span>\n";
                                echo "<ul>\n";
                                foreach ($menuitem['q'] as $question) {
                                        if ($question->getQuestionType() != QUESTION_TYPE_FREETEXT) {
                                                $options = Exam::getQuestionChoice($question->getQuestionText());
                                                $question->setQuestionText($options[0]);
                                        }
                                        printf("<li><a href=\"?exam=%d&amp;question=%d\" title=\"%s\">%s [%.01fp]</a></li>\n", $question->getExamID(), $question->getQuestionID(), strip_tags($question->getQuestionText()), strip_tags($question->getQuestionName()), $question->getQuestionScore());
                                }
                                echo "</ul>\n";
                        }

                        if (isset($menuitem['a'])) {
                                echo "<span id=\"menuhead\">" . _("Answered") . ":</span>\n";
                                echo "<ul>\n";
                                foreach ($menuitem['a'] as $question) {
                                        if ($question->getQuestionType() != QUESTION_TYPE_FREETEXT) {
                                                $options = Exam::getQuestionChoice($question->getQuestionText());
                                                $question->setQuestionText($options[0]);
                                        }
                                        printf("<li><a href=\"?exam=%d&amp;question=%d\" title=\"%s\">%s [%.01f]</a></li>\n", $question->getExamID(), $question->getQuestionID(), strip_tags($question->getQuestionText()), strip_tags($question->getQuestionName()), $question->getQuestionScore());
                                }
                                echo "</ul>\n";
                        }

                        echo "<span id=\"menuhead\">" . _("Show") . ":</span>\n";
                        echo "<ul>\n";
                        printf("<li><a href=\"?exam=%d\" title=\"%s\">%s</a></li>\n", $this->param->exam, _("Show the start page for this examination"), _("Start page"));
                        printf("<li><a href=\"?exam=%d&amp;question=all\" title=\"%s\">%s</a></li>\n", $this->param->exam, _("Show all questions at the same time"), _("All questions"));
                        echo "</ul>\n";
                }
        }

        //
        // Check that caller is authorized to access this exam.
        //
        private function checkExaminationAccess()
        {
                //
                // Allow contributors to bypass normal user checks (for previewing questions).
                //
                $manager = new Manager($this->param->exam);
                $this->author = $manager->isContributor(phpCAS::getUser());
                if ($this->author) {
                        $this->testcase = false;
                        return;
                }

                $data = Exam::getExamData(phpCAS::getUser(), $this->param->exam);
                if (!$data->hasExamID()) {
                        $this->fatal(_("No examination found!"), sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                }

                $now = time();
                $stime = strtotime($data->getExamStartTime());
                $etime = strtotime($data->getExamEndTime());

                if (!($stime <= $now && $now <= $etime)) {
                        $this->fatal(_("This examination is now closed!"), sprintf("<p>" . _("This examination ended %s and is now closed. If you think this is an error, please contact the examinator for further assistance.") . "</p>", strftime(DATETIME_FORMAT, $etime)));
                }

                $this->testcase = $data->getExamTestCase() == 'Y';
                $this->lockdown = $data->getExamLockDown() == 'Y';

                //
                // This block is only relevant if the exam is running in "real-mode"
                // where the client computer should be locked down.
                //
                if ($this->lockdown) {
                        try {
                                $locker = new LockerManager($_SERVER['REMOTE_ADDR'], $this->param->exam);
                                if (!$locker->locked()) {
                                        $locker->lockdown();
                                }
                        } catch (LockerException $exception) {
                                error_log($exception->getError());      // Log private message.
                                $this->fatal(_("Computer lockdown failed!"), sprintf("<p>" .
                                        _("Securing your computer for this examination has failed: %s") .
                                        "<p></p>" .
                                        _("If this is your own computer, make sure that the fwexamd service is started, otherwise contact the system administrator or examination assistant for further assistance. ") .
                                        _("The examiniation is inaccessable from this computer until the problem has been resolved.") .
                                        "</p>", $exception));
                        }
                }
        }

        //
        // Check that the requested question is part of this exam.
        //
        private function checkQuestionAccess()
        {
                $data = Exam::getQuestionData($this->param->question);
                if (!$data->hasQuestionID()) {
                        $this->fatal(_("Request parameter error!"), sprintf("<p>" . _("No question data was found for the requested question. This should not occure unless the request parameters has been explicit temperered.") . "</p>"));
                }
                if ($data->getExamID() != $this->param->exam) {
                        $this->fatal(_("Request parameter error!"), sprintf("<p>" . _("The requested question is not related to the requested examination. This should not occure unless the request parameters has been explicit temperered.") . "</p>"));
                }
        }

        //
        // Show available exams. It's quite possible that no exams has been approved for the user.
        //
        private function showAvailableExams()
        {
                $exams = Exam::getActiveExams(phpCAS::getUser());

                if ($exams->count() == 0) {
                        $this->fatal(_("No examination found!"), sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                }

                printf("<h3>" . _("Select the examination") . "</h3>\n");
                if ($exams->count() > 1) {
                        printf("<p>" . _("You have been assigned multiple examinations. Select the one to take by clicking on the examinations 'Begin' button.") . "</p>\n");
                }

                printf("<p>" . _("These examinations have been assigned to you, click on the button next to the description to begin the examination.") . "</p>\n");
                foreach ($exams as $exam) {
                        printf("<div class=\"examination\">\n");
                        printf("<div class=\"examhead\">%s</div>\n", $exam->getExamName());
                        printf("<div class=\"exambody\">%s<p>%s: <b>%s</b></p>\n", str_replace("\n", "<br>", $exam->getExamDescription()), _("The examination ends"), strftime(DATETIME_ISO, strtotime($exam->getExamEndTime())));

                        $form = new Form("index.php", "GET");
                        $form->addHidden("exam", $exam->getExamID());
                        $form->addSubmitButton("submit", _("Begin"));
                        $form->output();

                        printf("</div>\n");
                        printf("</div>\n");
                }
        }

        //
        // Show some simple instructions on how to doing the exam, along with
        // information about the selected exam.
        //
        private function showInstructions()
        {
                $exam = Exam::getExamData(phpCAS::getUser(), $this->param->exam);
                if (!$exam->hasExamID()) {
                        $this->fatal(_("No examination found!"), sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                }

                printf("<h3>%s</h3>\n", $exam->getExamName());
                printf("<p>" . _("In the left side menu are all questions that belongs to this examination. Questions already answered will appear under the 'Answered' section. The number within paranthesis is the score for each question.") . "</p>\n");
                printf("<p>" . _("Remember to <u>press the save button</u> when you have <u>answered a question</u>, and before moving on to another one. It's OK to save the answer to a question multiple times. Logout from the examination when you are finished.") . "</p>\n");
                printf("<p>" . _("Good luck!") . "</p>\n");

                if ($this->testcase) {
                        $text = new Content();
                        $text->addParagraph(_("This examination is running in test case mode. This mode allows you to review and work with the examination in the same way as the students will."));
                        $text->addParagraph(_("To exit, click either on the 'finish' or 'cancel' link at bottom of the page. Clicking 'finish' will stop the examination and allow you to correct and decode results, while 'cancel' will delete this test case."));
                        $text->addHeader(_("Important"), 6);
                        $text->addParagraph(_("Running in test case mode is non-destructive. The original examination remains unaffected as you are working entierly on a copy of it."));

                        $mbox = new MessageBox();
                        $mbox->setTitle(_("Running in test case mode!"));
                        $mbox->setMessage($text);
                        $mbox->display();
                }
        }

        //
        // Show all questions at once.
        //
        private function showQuestions()
        {
                $questions = Exam::getQuestions($this->param->exam, phpCAS::getUser());

                printf("<h3>" . _("Overview of all questions (no answers included)") . "</h3>\n");
                foreach ($questions as $question) {
                        if ($question->getQuestionType() != QUESTION_TYPE_FREETEXT) {
                                $options = Exam::getQuestionChoice($question->getQuestionText());
                                $question->setQuestionText($options[0]);
                        }
                        printf("<h5>%s: %s</h5><p>%s</p><p><a href=\"?exam=%d&amp;question=%d\">[%s]</a></p>\n", _("Question"), $question->getQuestionName(), str_replace("\n", "<br>", $question->getQuestionText()), $question->getExamID(), $question->getQuestionID(), _("Answer"));
                }
        }

        //
        // Show the selected question.
        //
        private function showQuestion()
        {
                $qdata = Exam::getQuestionData($this->param->question);
                $adata = Exam::getAnswerData($this->param->question, phpCAS::getUser());

                //
                // Use custom CSS depending on whether displaying media or not.
                //
                printf("<style type=\"text/css\">\n");
                if ($qdata->hasQuestionVideo() || $qdata->hasQuestionAudio() || $qdata->hasQuestionImage()) {
                        $qdata->setQuestionMedia(true);
                        include "../css/multimedia.css";  // Inline CSS
                } else {
                        printf("textarea.answer { width: 735px; height: 230px; }\n");
                }
                printf("</style>\n");

                printf("<div class=\"left\">\n");
                printf("<h3>%s %s [%.01fp]</h3>\n", _("Question"), $qdata->getQuestionName(), $qdata->getQuestionScore());

                //
                // Replace multiple '\n' (more than one) with row breaks:
                //
                $pattern = "/(\r\n){2,}|(\n|\r){4,}/";
                $replace = "\n<br/><br/>\n";

                // 
                // Expands handler escape sequencess:
                // 
                $scanner = new HandlerScanner($qdata->getQuestionText());

                // 
                // Output question text:
                // 
                if ($qdata->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        printf("<div class=\"question\">%s</div>\n", preg_replace($pattern, $replace, $scanner->expand()));
                } else {
                        $options = Exam::getQuestionChoice($scanner->expand());
                        printf("<div class=\"question\">%s</div>\n", preg_replace($pattern, $replace, $options[0]));
                }

                printf("<div class=\"answer\">\n");
                printf("<p class=\"answer\">" . _("Answer:") . "</p>\n");

                //
                // Output the question form including any already given answer:
                //
                $form = new Form("index.php", "POST");
                $form->setId("answerform");
                if (SESSION_AUTOSAVE != 0) {
                        $form->addHidden("autosave", false);
                }
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("question", $this->param->question);

                if ($qdata->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        $input = $form->addTextArea("answer", $adata->getAnswerText());
                        $input->setClass("answer");
                } elseif ($qdata->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE) {
                        $options = Exam::getQuestionChoice($qdata->getQuestionText());
                        $answers = Exam::getQuestionChoice($adata->getAnswerText());
                        foreach ($options[1] as $option) {
                                $input = $form->addRadioButton("answer[]", $option, $option);
                                if (in_array($option, $answers[1])) {
                                        $input->setChecked();
                                }
                                $form->addSpace();
                        }
                } elseif ($qdata->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
                        $options = Exam::getQuestionChoice($qdata->getQuestionText());
                        $answers = Exam::getQuestionChoice($adata->getAnswerText());
                        foreach ($options[1] as $option) {
                                $input = $form->addCheckBox("answer[]", $option, $option);
                                if (in_array($option, $answers[1])) {
                                        $input->setChecked();
                                }
                                $form->addSpace();
                        }
                }
                if (!$this->author) {
                        $form->addSpace();
                        $button = $form->addSubmitButton("save", _("Save"));
                        $button->setTitle(_("Save your answer in the database."));
                        $button = $form->addSubmitButton("next", _("OK"));
                        $button->setTitle(_("Save and move on to next unanswered question."));
                }
                $form->output();

                if (SESSION_AUTOSAVE != 0) {
                        printf("<script type=\"text/javascript\">\n");
                        printf("autosave_form('answerform', %d, true);\n", SESSION_AUTOSAVE);
                        printf("</script>\n");
                }
                printf("</div>\n");
                if ($this->author) {
                        MessageBox::show(MessageBox::information, _("This question is viewed in preview mode (for question author)."), _("Notice"));
                }
                if (isset($this->param->status) && $this->param->status == "ok") {
                        MessageBox::show(MessageBox::success, _("Your answer has been successful saved in the database."));
                }
                printf("</div>\n");

                if ($qdata->hasQuestionMedia()) {
                        printf("<div class=\"right\">\n");
                        if ($qdata->hasQuestionVideo()) {
                                printf("<div class=\"media\">\n");
                                printf("<h3>%s:</h3>\n", _("Video"));
                                $videoplayer = MediaPlayerFactory::createMediaPlayer($qdata->getQuestionVideo());
                                $videoplayer->output();
                                printf("</div>\n");
                        }
                        if ($qdata->hasQuestionAudio()) {
                                printf("<div class=\"media\">\n");
                                printf("<h3>%s:</h3>\n", _("Audio"));
                                $audioplayer = MediaPlayerFactory::createMediaPlayer($qdata->getQuestionAudio());
                                $audioplayer->output();
                                printf("</div>\n");
                        }
                        if ($qdata->hasQuestionImage()) {
                                printf("<div class=\"media\">\n");
                                printf("<h3>%s:</h3>\n", _("Image"));
                                printf("<a href=\"%s\" target=\"_blank\" title=\"%s\">", $qdata->getQuestionImage(), _("Click to open the URL in an external media player"));
                                printf("<img src=\"%s\" class=\"media\" />\n", $qdata->getQuestionImage());
                                printf("</a>\n");
                                printf("</div>\n");
                        }
                        printf("</div>\n");
                }
                printf("<br style=\"clear: both;\">\n");
        }

        //
        // Save the answer for an question.
        //
        private function saveQuestion()
        {
                if (is_array($this->param->answer)) {
                        $this->param->answer = json_encode($this->param->answer);
                }
                $this->param->answer = Database::getConnection()->escape($this->param->answer);

                Exam::setAnswer($this->param->exam, $this->param->question, phpCAS::getUser(), $this->param->answer);
                if (isset($this->param->save)) {
                        header(sprintf("location: index.php?exam=%d&question=%d&status=ok", $this->param->exam, $this->param->question));
                } elseif (isset($this->param->next)) {
                        $menuitem = self::getQuestions();
                        if (count($menuitem['q']) != 0) {
                                $next = $menuitem['q'][0];
                                $this->param->question = $next->getQuestionID();
                        }
                        header(sprintf("location: index.php?exam=%d&question=%d&status=ok", $this->param->exam, $this->param->question));
                }
        }

        //
        // Get questions classified as remaining or already answered.
        //
        private function getQuestions()
        {
                //
                // Calling getQuestions() will implicit create the question set bindings
                // in table answers if none exist for this user on this exam.
                //
                $questions = Exam::getQuestions($this->param->exam, phpCAS::getUser());

                //
                // Build the associative array of questions and answers. We are going to need
                // this array for proper sectioning of answered/unanswered questions.
                //
                // $array = array( "q" => array( ... ), "a" => array( ... );
                //
                $menuitem = array();
                foreach ($questions as $question) {
                        if ($question->getQuestionAnswered() == 'Y') {
                                $menuitem['a'][] = $question;
                        } else {
                                $menuitem['q'][] = $question;
                        }
                }
                return $menuitem;
        }

}

// 
// Validate request parameters and (if validate succeeds) render the page.
// 
$page = new ExaminationPage();
$page->render();
?>
