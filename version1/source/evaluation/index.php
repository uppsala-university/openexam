<?php

// 
// Copyright (C) 2010-2012, 2014 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/result/index.php
// Author: Anders Lövgren
// Date:   2010-12-16
//
// The page from where students can download results from the examinations.
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
class EvaluationPage extends BasePage
{

        private static $params = array(
                "exam" => parent::pattern_index,
        );
        private $data = null;   // The data for current exam (if any).

        public function __construct()
        {
                parent::__construct(_("Evaluation:"), self::$params);   // Internationalized with GNU gettext
        }

        //
        // Output the result page body.
        //
        public function printBody()
        {
                //
                // Bussiness logic:
                //
                $this->showAvailableExams();
        }

        //
        // Show available exams. It's quite possible that no exams has evaluation.
        //
        private function showAvailableExams()
        {
                $exams = Exam::getEvaluations(phpCAS::getUser());

                if ($exams->count() == 0) {
                        $this->fatal(_("No evaluations found!"), sprintf("<p>" . _("The system could not found any evaluations for your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
                }

                // 
                // Create evaluation data structure:
                // 
                $evaluations = array();

                foreach ($exams as $exam) {
                        if (!isset($evaluations[$exam->getExamID()])) {
                                $evaluations[$exam->getExamID()]['x'] = $exam;
                                $evaluations[$exam->getExamID()]['e'] = array();
                        }
                        if (!$exam->hasEvaluationName()) {
                                $exam->setEvaluationName($exam->getExamName());
                        }
                        $evaluations[$exam->getExamID()]['e'][$exam->getEvaluationID()] = $exam;
                }

                printf("<h3>" . _("Exam evaluations:") . "</h3>\n");
                printf("<p>" .
                    _("These are the evaluations that exists for your finished exams. ") .
                    _("Only still open (active) evaluations will be shown on this page. ") .
                    "</p>\n");

                //
                // Build the tree of examinations and evaluations:
                // 
                $tree = new TreeBuilder(_("Examinations"));
                $root = $tree->getRoot();

                foreach ($evaluations as $eid => $data) {
                        $exam = $data['x'];
                        $xnod = $root->addChild($exam->getExamName());
                        $xnod->addDates(strtotime($exam->getExamStartTime()), strtotime($exam->getExamEndTime()));
                        $pnod = $xnod->addChild(_("Evaluations"));
                        foreach ($data['e'] as $eval) {
                                $enod = $pnod->addChild($eval->getEvaluationName());
                                $enod->addLink(_("Open"), $eval->getEvaluationLink(), _("Click on this link to open this exam evaluation"));
                                if ($eval->hasEvaluationStartTime()) {
                                        $enod->addChild(sprintf(_("Opened %s"), DataRecord::formatDateTime($eval->getEvaluationStartTime())));
                                }
                                if ($eval->hasEvaluationEndTime()) {
                                        $enod->addChild(sprintf(_("Closes %s"), DataRecord::formatDateTime($eval->getEvaluationEndTime())));
                                }
                        }
                }

                //
                // Output the tree of decoded and finished examiniations:
                //
                $tree->output();
        }

}

// 
// Validate request parameters and (if validate succeeds) render the page.
// 
$page = new EvaluationPage();
$page->render();

?>
