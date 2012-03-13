<?php

// 
// Copyright (C) 2010-2012 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/admin/exams.php
// Author: Anders Lövgren
// Date:   2012-03-05
// 
// The exams admin page.
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
include "include/teacher/manager.inc";
include "include/export.inc";

// 
// The index page:
// 
class ExamAdminPage extends AdminPage
{

        private $params = array(
                "data"    => "/^(all|real|upcoming|today)$/",
                "exam"    => "/^\d+$/",
                "compact" => "/.*/",
                "submit"  => "/.*/",
                "export"  => "/.*/"
        );

        public function __construct()
        {
                parent::__construct(_("Exam Admin"), $this->params);
        }

        //
        // The main entry point. This is where all processing begins.
        //
        public function printBody()
        {
                if (!isset($this->param->data)) {
                        $this->param->data = 'upcoming';
                }

                if (isset($this->param->export)) {
                        if (isset($this->param->exam)) {
                                $this->exportExam();    // Stops execution here!
                        }

                        switch ($this->param->data) {
                                case 'all':
                                        $this->saveAllExams();
                                        break;
                                case 'real':
                                        $this->saveRealExams();
                                        break;
                                case 'upcoming':
                                        $this->saveUpcomingExams();
                                        break;
                                case 'today':
                                        $this->saveTodayExams();
                                        break;
                        }
                } else {
                        switch ($this->param->data) {
                                case 'all':
                                        $this->showAllExams();
                                        break;
                                case 'real':
                                        $this->showRealExams();
                                        break;
                                case 'upcoming':
                                        $this->showUpcomingExams();
                                        break;
                                case 'today':
                                        $this->showTodayExams();
                                        break;
                        }
                }
        }

        // 
        // Get all exams, including test cases.
        // 
        private function getAllExams()
        {
                return Exam::getExamList(null, null, true);
        }

        // 
        // Get all exams not being test cases.
        // 
        private function getRealExams()
        {
                return Exam::getExamList();
        }

        // 
        // Get all exams with a start date in the future.
        // 
        private function getUpcomingExams()
        {
                return Exam::getExamList(time());
        }

        // 
        // Get all exams starting today.
        // 
        private function getTodayExams()
        {
                $stime = mktime(0, 0, 0);
                $etime = $stime + 3600 * 24;

                return Exam::getExamList($stime, $etime);
        }

        // 
        // Save all exams, including test cases.
        // 
        private function saveAllExams()
        {
                $data = $this->getAllExams();
                $this->saveExamList($data);
        }

        // 
        // Save all exams not being test cases.
        // 
        private function saveRealExams()
        {
                $data = $this->getRealExams();
                $this->saveExamList($data);
        }

        // 
        // Save all exams with a start date in the future.
        // 
        private function saveUpcomingExams()
        {
                $data = $this->getUpcomingExams();
                $this->saveExamList($data);
        }

        // 
        // Save all exams starting today.
        // 
        private function saveTodayExams()
        {
                $data = $this->getTodayExams();
                $this->saveExamList($data);
        }

        // 
        // Save list of exams (export).
        // 
        private function saveExamList(&$data)
        {
                ob_end_clean();
                $name = sprintf("%s %s", $this->param->data, strftime(DATE_FORMAT));

                header("Content-type: text/tab-separated-values;charset=utf-8\n");
                header("Content-Disposition: attachment;filename=\"$name.tsv\"");
                header("Cache-Control: no-cache");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: no-cache");
                header("Expires: 0");

                printf("%s\t", _("ID"));
                printf("%s\t", _("Name"));
                printf("%s\t", _("Start"));
                printf("%s\t", _("End"));
                printf("%s\t", _("Created"));
                printf("%s\t", _("Updated"));
                printf("%s\t", _("Creator"));
                if (!isset($this->param->compact)) {
                        printf("%s\t", _("Description"));
                }
                printf("\n");

                foreach ($data as $r) {
                        printf("%s\t", $r->getExamID());
                        printf("%s\t", $r->getExamName());
                        printf("%s\t", $r->getExamStartTime());
                        printf("%s\t", $r->getExamEndTime());
                        printf("%s\t", $r->getExamCreated());
                        printf("%s\t", $r->getExamUpdated());
                        printf("%s\t", $r->getExamCreator());
                        if (!isset($this->param->compact)) {
                                printf("%s\t", $r->getExamDescription());
                        }
                        printf("\n");
                }
                exit(0);
        }

        // 
        // Show all exams, including test cases.
        // 
        private function showAllExams()
        {
                $data = $this->getAllExams();
                $this->printExamList($data);
        }

        // 
        // Show all exams not being test cases.
        // 
        private function showRealExams()
        {
                $data = $this->getRealExams();
                $this->printExamList($data);
        }

        // 
        // Show all exams with a start date in the future.
        // 
        private function showUpcomingExams()
        {
                $data = $this->getUpcomingExams();
                $this->printExamList($data);
        }

        // 
        // Show all exams starting today.
        // 
        private function showTodayExams()
        {
                $data = $this->getTodayExams();
                $this->printExamList($data);
        }

        //
        // Export complete exam.
        //
        private function exportExam()
        {
                $exporter = new Export($this->param->exam);
                $exporter->send();
                exit(0);
        }

        private function printExamList(&$data)
        {
                printf("<h3>%s</h3>\n", _("Exams"));
                printf("<p>" . _("This page shows exams created by all system users matching the selected filter option.") . "</p>\n");

                $options = array(
                        "all"      => _("All"),
                        "real"     => _("Real"),
                        "upcoming" => _("Upcoming"),
                        "today"    => _("Today")
                );

                $form = new Form("exams.php");
                $combo = $form->addComboBox("data");
                foreach ($options as $name => $text) {
                        $option = $combo->addOption($name, $text);
                        if ($name == $this->param->data) {
                                $option->setSelected();
                        }
                }
                $combo->setLabel(_("Show"));
                $form->addSubmitButton("submit", _("Update"));
                $form->addSubmitButton("export", _("Export"));
                $check = $form->addCheckBox("compact", _("Compact output"));
                $check->setLabel();
                if (isset($this->param->compact)) {
                        $check->setChecked();
                }
                $form->output();
                printf("<br/>\n");

                if ($data->count() == 0) {
                        MessageBox::show(MessageBox::information, _("No records matching the selected filter options."));
                        return;
                }

                $table = new Table();
                $row = $table->addRow();
                $row->addHeader(_("ID"));
                $row->addHeader(_("Name"));
                $row->addHeader(_("Start"));
                $row->addHeader(_("End"));

                $table->addRow()->addData()->setStyle("background: white");

                foreach ($data as $r) {

                        // 
                        // +-------+---------+----------+--------+
                        // |  ID   |  Name   |  Start   |  End   |
                        // +-------+---------+----------+--------+
                        // | Description:                        |
                        // |  ...                                |
                        // +-----------------+-------------------+
                        // | Created: ...    | Creator: ...      |
                        // +-----------------+-------------------+
                        // 

                        $row = $table->addRow();
                        $row->addData($r->getExamID());
                        $row->addData($r->getExamName());
                        $row->addData($r->getExamStartTime());
                        $row->addData($r->getExamEndTime());
                        $cell = $row->addData(_("Open"));       // View properties
                        $cell->setLink(sprintf("../teacher/manager.php?exam=%d&action=show", $r->getExamID()));
                        $cell = $row->addData(_("Adjust"));     // Adjust scores
                        $cell->setLink(sprintf("adjust.php?exam=%d", $r->getExamID()));
                        $cell = $row->addData(_("Export"));
                        $cell->setLink(sprintf("?exam=%d&export=1", $r->getExamID()));

                        if (!isset($this->param->compact)) {
                                $row = $table->addRow();
                                $cell = $row->addData(sprintf("<b>%s:</b><br/>%s\n", _("Description"), $r->getExamDescription()));
                                $cell->setColspan(4);

                                $row = $table->addRow();
                                $cell = $row->addData(sprintf("<b>%s:</b> %s", _("Created"), $r->getExamCreated()));
                                $cell->setColspan(2);
                                $cell = $row->addData(sprintf("<b>%s:</b> %s", _("Creator"), $r->getExamCreator()));
                                $cell->setColspan(2);

                                $table->addRow()->addData()->setStyle("background: white");
                        }
                }
                $table->output();

                printf("<p><b>%s:</b> %d</p>\n", _("Count"), $data->count());
        }

}

$page = new ExamAdminPage();
$page->render();
?>
