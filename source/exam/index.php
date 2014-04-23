<?php

// 
// Copyright (C) 2010-2012, 2014 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/exam/index.php
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// This is the page where students do their exam.
//
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
include "include/media.inc";
include "include/ldap.inc";

// 
// Needed to bypass access checks for contributors (in preview mode):
// 
include "include/teacher/manager.inc";

// 
// Enable form auto save and AJAX POST. The values are number of seconds 
// between form submit if auto save is non-zero.
// 
if (!defined("FORM_AUTO_SAVE")) {
        define("FORM_AUTO_SAVE", 0);
}
if (!defined("FORM_AJAX_SEND")) {
        define("FORM_AJAX_SEND", true);
}
if (!defined("FORM_LINK_SAVE")) {
        define("FORM_LINK_SAVE", false);
}

class FatalException extends RuntimeException
{

        private $header;

        public function __construct($header, $message, $code = 0, $previous = null)
        {
                parent::__construct($message, $code, $previous);
                $this->header = $header;
        }

        public function getHeader()
        {
                return $this->header;
        }

}

// 
// This class implements a standard page.
// 
class ExaminationPage extends BasePage
{

        private $author = false;    // Running in question author mode.
        private $lockdown = false;  // This examination has lockdown mode enabled.
        private $testcase = false;  // This examination is a testcase.
        private $user = null;       // Current authenticated user.
        //
        // All possible request parameters should be added here along with
        // the regex pattern to validate its value against.
        //
        private static $params = array(
                "exam"     => parent::pattern_index,
                "answer"   => parent::pattern_textarea,
                "question" => "/^(\d+|all|exam)$/",
                "status"   => "/^(ok)$/",
                "save"     => parent::pattern_textline, // button
                "next"     => parent::pattern_textline, // button
                "ajax"     => parent::pattern_index
        );

        //
        // Construct the exam page.
        //
        public function __construct()
        {
                parent::__construct(_("Examination:"), self::$params);   // Internationalized with GNU gettext
                $this->initialize();
        }

        public function printHeader()
        {
                parent::printHeader();
                printf("<script type=\"text/javascript\" language=\"javascript\" src=\"/openexam/js/jquery/jquery.min.js\"></script>\n");
        }

        //
        // The template page body.
        //
        public function printBody()
        {
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
                        } elseif ($this->param->question == "exam") {
                                $exam = Exam::getExamData($this->user, $this->param->exam);
                                $this->showProperties($exam);
                        } elseif (isset($this->param->answer)) {
                                $this->saveRouter((object) $this->saveQuestion());
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
                        $media = new MediaLibrary($this->param->exam);
                        if (count($media->resource) != 0) {
                                echo "<span id=\"menuhead\">" . _("Resources") . ":</span>\n";
                                echo "<ul>\n";
                                foreach ($media->resource as $file) {
                                        printf("<li><a href=\"%s\" title=\"%s\">%s</a></li>\n", $file->url, $file->name, $file->name);
                                }
                                echo "</ul>\n";
                        }

                        $menuitem = self::getQuestions();

                        if (isset($menuitem['q'])) {
                                echo "<span id=\"menuhead\">" . _("Questions") . ":</span>\n";
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
                        printf("<li><a href=\"?exam=%d&amp;question=all\" title=\"%s\">%s</a></li>\n", $this->param->exam, _("Show all questions at the same time"), _("All questions"));
                        printf("<li><a href=\"?exam=%d\" title=\"%s\">%s</a></li>\n", $this->param->exam, _("Show the start page for this examination"), _("Start page"));
                        printf("<li><a href=\"?exam=%d&question=exam\" title=\"%s\">%s</a></li>\n", $this->param->exam, _("Show properties for this examination."), _("Properties"));
                        echo "</ul>\n";

                        $exams = Exam::getActiveExams($this->user);
                        if ($exams->count() > 1) {
                                echo "<span id=\"menuhead\">" . _("Examinations") . ":</span>\n";
                                echo "<ul>\n";
                                foreach ($exams as $exam) {
                                        printf("<li><a href=\"?exam=%d\" title=\"%s\">%s</a></li>\n", $exam->getExamID(), $exam->getExamDescription(), $exam->getExamName());
                                }
                                echo "</ul>\n";
                        }
                        $this->printUser();
                }
        }

        // 
        // Display the name and personal number from LDAP. I'm not even
        // sure that this is legal.
        // 
        private function printUser()
        {
                try {
                        $ldap = new LdapSearch(LdapConnection::instance());
                        $ldap->setAttributeFilter(array('cn', 'norEduPersonNIN'));       // limit returned attr
                        $user = $ldap->searchUid(phpCAS::getUser())->first();

                        $name = (string) ($user->getCN());
                        $upnr = (string) ($user->getNorEduPersonNIN());

                        printf("<div class=\"userinfo\"><span class=\"name\">%s</span> - <span>%s</span></div>\n", $name, $upnr);
                } catch (LdapException $exception) {
                        error_log($exception);
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
                $this->author = $manager->isContributor($this->user);
                if ($this->author) {
                        $this->testcase = false;
                        return;
                }

                $data = Exam::getExamData($this->user, $this->param->exam);
                if (!$data->hasExamID()) {
                        throw new FatalException(_("No examination found!"), sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                }

                $now = time();
                $stime = strtotime($data->getExamStartTime());
                $etime = strtotime($data->getExamEndTime());

                if (!($stime <= $now && $now <= $etime)) {
                        throw new FatalException(_("This examination is now closed!"), sprintf("<p>" . _("This examination ended %s and is now closed. If you think this is an error, please contact the examinator for further assistance.") . "</p>", strftime(DATETIME_FORMAT, $etime)));
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
                                throw new FatalException(_("Computer lockdown failed!"), sprintf("<p>" .
                                    _("Securing your computer for this examination has failed: %s") .
                                    "<p></p>" .
                                    _("If this is your own computer, make sure that the fwexamd service is started, otherwise contact the system administrator or examination assistant for further assistance. ") .
                                    _("The examiniation is inaccessable from this computer until the problem has been resolved.") .
                                    "</p>", $exception), 0, $exception);
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
                        throw new FatalException(_("Request parameter error!"), sprintf("<p>" . _("No question data was found for the requested question. This should not occure unless the request parameters has been explicit temperered.") . "</p>"));
                }
                if ($data->getExamID() != $this->param->exam) {
                        throw new FatalException(_("Request parameter error!"), sprintf("<p>" . _("The requested question is not related to the requested examination. This should not occure unless the request parameters has been explicit temperered.") . "</p>"));
                }
        }

        //
        // Show available exams. It's quite possible that no exams has been approved for the user.
        //
        private function showAvailableExams()
        {
                $exams = Exam::getActiveExams($this->user);

                if ($exams->count() == 0) {
                        throw new FatalException(_("No examination found!"), sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                }

                printf("<h3>" . _("Select the examination") . "</h3>\n");
                if ($exams->count() > 1) {
                        printf("<p>" . _("You have been assigned multiple examinations. Select the one to take by clicking on the examinations 'Begin' button.") . "</p>\n");
                }

                printf("<p>" . _("These examinations have been assigned to you, click on the button next to the description to begin the examination.") . "</p>\n");
                foreach ($exams as $exam) {
                        $this->showProperties($exam, true);
                }
        }

        // 
        // Show properties for given exam.
        // 
        private function showProperties($exam, $form = false)
        {
                printf("<div class=\"examination\">\n");
                printf("<div class=\"examhead\">%s</div>\n", $exam->getExamName());
                printf("<div class=\"exambody\">%s<p>%s: <b>%s</b></p>\n", str_replace("\n", "<br>", $exam->getExamDescription()), _("The examination ends"), strftime(DATETIME_ISO, strtotime($exam->getExamEndTime())));

                if ($form) {
                        $form = new Form("index.php", "GET");
                        $form->addHidden("exam", $exam->getExamID());
                        $form->addSubmitButton("submit", _("Begin"));
                        $form->output();
                }

                printf("</div>\n");
                printf("</div>\n");
        }

        //
        // Show some simple instructions on how to doing the exam, along with
        // information about the selected exam.
        //
        private function showInstructions()
        {
                $exam = Exam::getExamData($this->user, $this->param->exam);
                if (!$exam->hasExamID()) {
                        throw new FatalException(_("No examination found!"), sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
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
                $questions = Exam::getQuestions($this->param->exam, $this->user);

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
        private function showQuestion($answer = null)
        {
                // 
                // Restore answer if requested:
                // 
                if (!isset($answer)) {
                        $qdata = Exam::getQuestionData($this->param->question);
                        $adata = Exam::getAnswerData($this->param->question, $this->user);
                } else {
                        $qdata = Exam::getQuestionData($this->param->question);
                        $adata = new DataRecord(array('answertext' => $answer));
                }

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
                // Expands handler escape sequences:
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
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("question", $this->param->question);

                if ($qdata->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        $input = $form->addTextArea("answer", $adata->getAnswerText());
                        $input->setClass("answer");
                } elseif ($qdata->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE) {
                        $options = Exam::getQuestionChoice($qdata->getQuestionText());
                        $answers = Exam::getQuestionChoice($adata->getAnswerText());
                        foreach ($options[1] as $option) {
                                $input = $form->addRadioButton("answer[]", htmlspecialchars($option), $option);
                                if (in_array($option, $answers[1])) {
                                        $input->setChecked();
                                }
                                $form->addSpace();
                        }
                } elseif ($qdata->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
                        $options = Exam::getQuestionChoice($qdata->getQuestionText());
                        $answers = Exam::getQuestionChoice($adata->getAnswerText());
                        foreach ($options[1] as $option) {
                                $input = $form->addCheckBox("answer[]", htmlspecialchars($option), $option);
                                if (in_array($option, $answers[1])) {
                                        $input->setChecked();
                                }
                                $form->addSpace();
                        }
                }
                if (!$this->author) {
                        $form->addSpace();
                        $button = $form->addSubmitButton("save", _("Save"));
                        $button->setId('save');
                        $button->setTitle(_("Save your answer in the database."));
                        $button = $form->addSubmitButton("next", _("OK"));
                        $button->setId('next');
                        $button->setTitle(_("Save and move on to next unanswered question."));
                }
                $form->output();

                if (FORM_AUTO_SAVE != 0) {
                        printf("<script>\n");
                        printf("form_auto_save('answerform', %d, true);\n", FORM_AUTO_SAVE);
                        printf("</script>\n");
                }
                if (FORM_AJAX_SEND) {
                        printf("<script>\n");
                        printf("form_ajax_send('answerform');\n");
                        printf("</script>\n");
                }
                if (FORM_LINK_SAVE) {
                        printf("<script>\n");
                        printf("form_link_save('answerform');\n");
                        printf("</script>\n");
                }

                printf("</div>\n");
                if ($this->author) {
                        MessageBox::show(MessageBox::information, _("This question is viewed in preview mode (for question author)."), _("Notice"));
                }
                $this->showResult();
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

        private function showResult()
        {
                if (isset($this->result)) {
                        if ($this->result->status == "ok") {
                                MessageBox::show(MessageBox::success, $this->result->message, null);
                        } elseif ($this->result->status == "failed") {
                                MessageBox::show(MessageBox::warning, $this->result->message, null);
                        } elseif ($this->result->status == "info") {
                                MessageBox::show(MessageBox::information, $this->result->message, null);
                        }
                } else {
                        printf("<div class=\"result\" id=\"result\" style=\"display:none;\">\n");
                        foreach (array(
                            MessageBox::error,
                            MessageBox::warning,
                            MessageBox::information,
                            MessageBox::success
                        ) as $type) {
                                printf("<div class=\"result-%s\" style=\"display:inline;\">\n", $type);
                                $msgbox = new MessageBox($type);
                                $msgbox->setId(sprintf("result-%s", $type));
                                $msgbox->setTitle(null);
                                $msgbox->display();
                                printf("</div>\n");
                        }
                        printf("</div>\n");
                }
        }

        // 
        // Post answer save handler.
        //
        private function saveRouter($result)
        {
                if ($result->status == "failed") {
                        $this->result = $result;
                        $this->showQuestion($this->param->answer);
                } elseif ($result->status == "info") {
                        $this->result = $result;
                        $this->showQuestion();
                } elseif (isset($this->param->save)) {
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
        // Save the answer for an question.
        //
        private function saveQuestion()
        {
                if (is_array($this->param->answer)) {
                        $this->param->answer = json_encode($this->param->answer);
                } else {
                        $this->param->answer = trim($this->param->answer);
                }

                if (strlen($this->param->answer) == 0) {
                        return array(
                                "status"  => "info",
                                "message" => _("Received an empty answer. These messages are ignored to <u>prevent data loss</u>.")
                        );
                }

                try {
                        $answer = Database::getConnection()->escape($this->param->answer);
                        Exam::setAnswer($this->param->exam, $this->param->question, $this->user, $answer);
                        return array(
                                "status"  => "ok",
                                "message" => _("Your answer has been successful saved in the database.")
                        );
                } catch (Exception $exception) {
                        $this->saveState();
                        error_log($exception);
                        return array(
                                "status"  => "failed",
                                "message" => sprintf("<b><u>%s</u></b><br/><br/>%s", _("Failed write answer to database."), _("Please wait a few seconds before retry saving. Do not switch to another question before your answer has been successful saved. If you do, then all your changes since the last save will be lost.")
                                )
                        );
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
                $questions = Exam::getQuestions($this->param->exam, $this->user);

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

        // 
        // Save current state for debugging.
        // 
        private function saveState()
        {
                $this->server = $_SERVER;
                $this->request = $_REQUEST;
                $this->session = $_SESSION;
                $filename = tempnam(sys_get_temp_dir(), "openexam");
                file_put_contents($filename, print_r($this, true));
        }

        // 
        // Called to initialize this object.
        // 
        private function initialize()
        {
                if (isset($_SESSION['caller'])) {
                        if ($_SESSION['caller']['remote'] != $_SERVER['REMOTE_ADDR']) {
                                throw new RuntimeException(_("Remote caller don't match recorded value, possible session hijack"));
                        } else {
                                $this->user = $_SESSION['caller']['user'];
                        }
                } else {
                        $this->user = phpCAS::getUser();
                        $_SESSION['caller']['user'] = $this->user;
                        $_SESSION['caller']['remote'] = $_SERVER['REMOTE_ADDR'];
                }

                if (!isset($this->user) || strlen($this->user) == 0) {
                        $this->saveState();
                        throw new RuntimeException(
                        _("The user name is unknown and the script has been halted to prevent data loss. ") .
                        _("Please try again when the logon service is back. ")
                        );
                }

                if (isset($this->param->status) && $this->param->status == "ok") {
                        $this->result = (object) array(
                                    "status"  => "ok",
                                    "message" => _("Your answer has been successful saved in the database.")
                        );
                }
        }

        // 
        // Process the request.
        // 
        public function process()
        {
                try {
                        //
                        // Authorization first:
                        //
                        if (isset($this->param->exam)) {
                                $this->checkExaminationAccess();
                                if (isset($this->param->question) && (
                                    $this->param->question != "all" &&
                                    $this->param->question != "exam")) {
                                        $this->checkQuestionAccess();
                                }
                        }

                        // 
                        // Handles both AJAX requests and request/response method for
                        // submitted form (using the render() callback).
                        // 
                        if (isset($this->param->ajax) && (isset($this->param->save) || isset($this->param->next))) {
                                echo json_encode($this->saveQuestion());
                        } elseif (isset($this->param->next) && $this->param->next == 'route') {
                                $this->saveRouter($this->param);
                        } else {
                                $this->render();
                        }
                } catch (FatalException $exception) {
                        $this->fatal($exception->getHeader(), $exception->getMessage());
                } catch (DatabaseException $exception) {
                        error_log(sprintf("%s: %s", get_class($exception), $exception->getMessage()));
                        $this->fatal(_('Database error'), _("Please wait a few seconds before retry saving. Do not switch to another question before your answer has been successful saved. If you do, then all your changes since the last save will be lost."));
                } catch (Exception $exception) {
                        $this->fatal(get_class($exception), $exception->getMessage());
                }
        }

        // 
        // Error report back to caller.
        // 
        public function fatal($title, $message, $exitcode = 1)
        {
                if (isset($this->param->ajax)) {
                        echo json_encode(array(
                                'status'  => 'failed',
                                'header'  => $title,
                                'message' => $message
                        ));
                } else {
                        parent::fatal($title, $message, $exitcode);
                }
        }

}

// 
// Validate request parameters and (if validate succeeds) render the page.
// 
$page = new ExaminationPage();
$page->process();

?>
