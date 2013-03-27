<?php

// 
// Copyright (C) 2013 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/export.php
// Author: Anders Lövgren
// Date:   2013-03-25
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
include "include/teacher/manager.inc";
include "include/teacher/correct.inc";
include "include/export.inc";

// 
// The export page:
// 
class ExportPage extends TeacherPage
{

        private static $params = array(
                "exam"    => parent::pattern_index,
                "show"    => "/^(basic|advanced)$/",
                "action"  => "/^(show|export)$/",
                "format"  => "/^(native|word|excel|pdf|plain)$/",
                "options" => parent::pattern_index,
                "order"   => "/^(state|name|date)$/"
        );

        public function __construct()
        {
                $this->param->order = "date";
                $this->param->show = "basic";
                $this->param->action = "show";

                parent::__construct(_("Export Page"), self::$params);
        }

        //
        // The main entry point. This is where all processing begins.
        //
        public function printBody()
        {
                echo "<h3>" . _("Export Page") . "</h3>\n";

                //
                // Authorization first:
                //
                if (isset($this->param->exam)) {
                        $this->checkAccess($this->param->exam);
                }

                //
                // Bussiness logic:
                //
                if (isset($this->param->exam)) {
                        if ($this->param->action == "show") {
                                $this->showOptions();
                        } elseif ($this->param->action == "export") {
                                $this->assert(array(
                                        "format",
                                        "options"));
                                $this->export();
                        }
                } else {
                        self::showAvailableExams();
                }
        }

        // 
        // Display the export options page.
        // 
        private function showOptions()
        {
                $mode = array(
                        "basic"    => _("Basic"),
                        "advanced" => _("Advanced")
                );
                $disp = array(
                );
                printf("<span class=\"links viewmode\">\n");
                foreach ($mode as $name => $text) {
                        if ($this->param->show != $name) {
                                $disp[] = sprintf("<a href=\"?exam=%d&amp;action=show&amp;show=%s\">%s</a>", $this->param->exam, $name, $text);
                        } else {
                                $disp[] = $text;
                        }
                }
                printf("%s: %s\n", _("Show"), implode(", ", $disp));
                printf("</span>\n");

                if ($this->param->show == "advanced") {
                        $this->showOptionsAdvanced();
                } else {
                        $this->showOptionsBasic();
                }

                MessageBox::show(MessageBox::information, _("Use OpenExam native project format to export this examination in a format that can be imported again later."));
        }

        // 
        // Show basic options.
        // 
        private function showOptionsBasic()
        {
                printf("<p>" .
                    _("This page shows links for commonly used export cases. ") .
                    _("Use the advanced tab to utilize the full set of export options. ") .
                    "</p>");

                $tree = new TreeBuilder(_("Choose export format:"));
                $root = $tree->getRoot();
                $node = $root->addChild(_("Microsoft Word 2007 document"));
                $node->setLink(sprintf("?exam=%d&amp;action=export&amp;format=word&amp;options=%d", $this->param->exam, OPENEXAM_EXPORT_INCLUDE_DEFAULT));
                $node = $root->addChild(_("OpenExam project data"));
                $node->setLink(sprintf("?exam=%d&amp;action=export&amp;format=native&amp;options=%d", $this->param->exam, OPENEXAM_EXPORT_INCLUDE_DEFAULT));
                $node = $root->addChild(_("Adobe PDF document"));
                $node->setLink(sprintf("?exam=%d&amp;action=export&amp;format=native&amp;options=%d", $this->param->exam, OPENEXAM_EXPORT_INCLUDE_DEFAULT));
                $tree->output();
        }

        // 
        // Show advanced options.
        // 
        private function showOptionsAdvanced()
        {
                $form = new Form("export.php");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("action", "export");
                $combo = $form->addComboBox("format");
                $combo->setLabel(_("Format"));
                $combo->addOption("word", _("Microsoft Word 2007 document"));
                $combo->addOption("excel", _("Microsoft Excel 2007 document"));
                $combo->addOption("native", _("OpenExam project data"));
                $combo->addOption("pdf", _("Adobe PDF document"));
                $combo->addOption("plain", _("Plain text document"));
                $form->addSpace();
                $check = $form->addCheckBox("options[]", OPENEXAM_EXPORT_INCLUDE_PROJECT, _("Properties"));
                $check->setLabel(_("Include"));
                $check->setChecked();
                $check = $form->addCheckBox("options[]", OPENEXAM_EXPORT_INCLUDE_TOPICS, _("Topics"));
                $check->setLabel();
                $check->setChecked();
                $check = $form->addCheckBox("options[]", OPENEXAM_EXPORT_INCLUDE_QUESTIONS, _("Questions"));
                $check->setLabel();
                $check->setChecked();
                $check = $form->addCheckBox("options[]", OPENEXAM_EXPORT_INCLUDE_ROLES, _("Roles"));
                $check->setLabel();
                $check = $form->addCheckBox("options[]", OPENEXAM_EXPORT_INCLUDE_ANSWERS, _("Answers"));
                $check->setLabel();

                $button = $form->addSubmitButton("export", _("Export"));
                $button->setLabel();
                $button = $form->addResetButton("reset", _("Reset"));
                $form->output();
        }

        // 
        // Export the examination.
        // 
        private function export()
        {
                for ($options = 0, $i = 0; $i < count($this->param->options); ++$i) {
                        $options |= $this->param->options[$i];
                }
                $exporter = new Export($this->param->exam, $this->param->format, $options);
                $exporter->send();
                exit(0);
        }

        //
        // Show all exams the current user is the owner of.
        //
        private function showAvailableExams()
        {
                $utils = new TeacherUtils($this, phpCAS::getUser());
                $utils->listManageable($this->param->order);
        }

        //
        // Verify that the caller has been granted the required role.
        //
        private function checkAccess()
        {
                if (isset($this->param->exam)) {
                        if (!$this->manager->isCreator(phpCAS::getUser())) {
                                $this->fatal(_("Access denied!"), sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), "creator"));
                        }
                } else {
                        if ($this->roles->getCreatorRoles() == 0 && $this->roles->getManagerRoles() == 0) {
                                $this->fatal(_("Access denied!"), _("Only users granted the teacher role or being the creator on at least one exam can access this page. The script processing has halted."));
                        }
                }
        }

}

$page = new ExportPage();
$page->render();
?>
