<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/result/index.php
// Author: Anders Lövgren
// Date:   2010-12-16
//
// The page from where students can download results from the examinations.
//

if (!file_exists("../../conf/database.conf")) {
        header("location: admin/setup.php?reason=database");
}
if (!file_exists("../../conf/config.inc")) {
        header("location: admin/setup.php?reason=config");
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

// 
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include bussiness logic:
//
include "include/exam.inc";
include "include/pdf.inc";
include "include/scoreboard.inc";
include "include/teacher/manager.inc";
include "include/teacher/correct.inc";

// 
// This class implements a standard page.
// 
class ResultPage extends BasePage
{

        //
        // All possible request parameters should be added here along with
        // the regex pattern to validate its value against.
        //
        private $params = array(
                "exam" => "/^\d+$/",
                "action" => "/^(details|download)$/",
                "format" => "/^(pdf|html)$/"
        );
        private $data = null;   // The data for current exam (if any).

        public function __construct()
        {
                parent::__construct(_("Result:"));   // Internationalized with GNU gettext
        }

        //
        // Output the result page body.
        //
        public function printBody()
        {
                //
                // Authorization first:
                //
                if (isset($_REQUEST['exam'])) {
                        self::checkAccess($_REQUEST['exam']);
                } else {
                        self::checkAccess();
                }

                //
                // Bussiness logic:
                //
                if (!isset($_REQUEST['exam']) && !isset($_REQUEST['action'])) {
                        self::showAvailableExams();
                } else {
                        if ($_REQUEST['action'] == "download") {
                                if (!isset($_REQUEST['format'])) {
                                        $_REQUEST['format'] = "pdf";
                                }
                                self::sendExam($_REQUEST['exam'], $_REQUEST['format']);
                        } elseif ($_REQUEST['action'] == "details") {
                                self::showExam($_REQUEST['exam']);
                        }
                }
        }

        //
        // Check that caller is authorized to access this exam or don't have
        // an currently active examination.
        //
        private function checkAccess($exam = 0)
        {
                if ($exam == 0) {
                        $exams = Exam::getActiveExams(phpCAS::getUser());
                        if ($exams->count() > 0) {
                                ErrorPage::show(_("Access denied!"),
                                                _("Access to results from your previous completed examinations is not available while another examination is taking place."));
                                exit(1);
                        }
                } else {
                        $this->data = Exam::getExamData(phpCAS::getUser(), $exam);
                        if (!$this->data->hasExamID()) {
                                ErrorPage::show(_("No examination found!"),
                                                _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance."));
                                exit(1);
                        }
                }
        }

        //
        // Show available exams. It's quite possible that no exams has been approved for the user.
        //
        private function showAvailableExams()
        {
                $exams = Exam::getFinishedExams(phpCAS::getUser());

                if ($exams->count() == 0) {
                        ErrorPage::show(_("No examination found!"),
                                        sprintf("<p>" . _("The system could not found any finished examiniations for your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                        exit(1);
                }

                //
                // Classify examinations as finished or decoded.
                // 
                $data = array('f' => array(), 'd' => array());
                foreach ($exams as $exam) {
                        if ($exam->getExamDecoded() == 'Y') {
                                $data['d'][] = $exam;
                        } else {
                                $data['f'][] = $exam;
                        }
                }
                printf("<h3>" . _("Completed examinations:") . "</h3>\n");
                printf("<p>" . _("Your allready completed examinations are either in the state finished or decoded. ") .
                        _("An finished examination is still in the phase of being corrected, while a decoded has already been corrected and got results ready to be downloaded.") .
                        "</p>");

                //
                // Build the tree of decoded and finished examinations:
                // 
                $tree = new TreeBuilder(_("Examinations"));
                $root = $tree->getRoot();

                if (count($data['d']) != 0) {
                        $sect = $root->addChild(_("Decoded"));
                        foreach ($data['d'] as $exam) {
                                $node = $sect->addChild($exam->getExamName());
                                $node->addDates(strtotime($exam->getExamStartTime()), strtotime($exam->getExamEndTime()));
                                $node->addLink(_("Download"),
                                        sprintf("?exam=%d&amp;action=download", $exam->getExamID()));
                                $node->addLink(_("Show"),
                                        sprintf("?exam=%d&amp;action=download&amp;format=html", $exam->getExamID()));
                                $node->addLink(_("Details"),
                                        sprintf("?exam=%d&amp;action=details", $exam->getExamID()));
                        }
                }

                if (count($data['f']) != 0) {
                        $sect = $root->addChild(_("Finished"));
                        foreach ($data['f'] as $exam) {
                                $node = $sect->addChild($exam->getExamName());
                                $node->addDates(strtotime($exam->getExamStartTime()), strtotime($exam->getExamEndTime()));
                                $node->addLink(_("Details"),
                                        sprintf("?exam=%d&amp;action=details", $exam->getExamID()));
                        }
                }

                //
                // Output the tree of decoded and finished examiniations:
                //
                $tree->output();
        }

        //
        // Send result to peer as either PDF or HTML.
        //
        private function sendExam($exam, $format)
        {
                //
                // Make sure we don't leak information:
                //
                if ($this->data->getExamDecoded() != 'Y') {
                        ErrorPage::show(_("No access"),
                                        _("This examiniation has not yet been decoded."));
                        exit(1);
                }

                //
                // Turn off and clear output buffer. Send data to peer in
                // the requested format.
                //
                ob_end_clean();
                $pdf = new ResultPDF($exam);
                $pdf->setFormat($format);
                $pdf->send($this->data->getStudentID());
                exit(0);
        }

        //
        // Show details for this examination.
        // 
        private function showExam($exam)
        {
                printf("<h3>" . _("Examination details") . "</h3>\n");
                printf("<p>" . _("Showing description for examiniation <u>%s</u> on <u>%s</u>") . ":</p>\n",
                        $this->data->getExamName(),
                        strftime(DATE_FORMAT, strtotime($this->data->getExamStartTime())));
                printf("<div class=\"examination\">\n");
                printf("<div class=\"examhead\">%s</div>\n",
                        $this->data->getExamName());
                printf("<div class=\"exambody\">%s</div>\n",
                        str_replace("\n", "<br/>", $this->data->getExamDescription()));
                printf("</div>\n");
        }

        //
        // Validates request parameters.
        //
        public function validate()
        {
                foreach ($this->params as $param => $pattern) {
                        if (isset($_REQUEST[$param])) {
                                if (is_array($_REQUEST[$param])) {
                                        foreach ($_REQUEST[$param] as $value) {
                                                if (!preg_match($pattern, $value)) {
                                                        ErrorPage::show(_("Request parameter error!"),
                                                                        sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."),
                                                                                $param, $pattern));
                                                        exit(1);
                                                }
                                        }
                                } elseif (!preg_match($pattern, $_REQUEST[$param])) {
                                        ErrorPage::show(_("Request parameter error!"),
                                                        sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."),
                                                                $param, $pattern));
                                        exit(1);
                                }
                        }
                }
        }

}

// 
// Validate request parameters and (if validate succeeds) render the page.
// 
$page = new ResultPage();
$page->validate();
$page->render();
?>
