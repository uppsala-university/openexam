<?php

// 
// Copyright (C) 2010-2014 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/manager.php
// Author: Anders Lövgren
// Date:   2010-04-26
// 
// This script is for managing exams (the actual test). It let people assigned the
// teacher role to define new examinations and edit old ones. An examination is
// defined in the exam table.
// 
// The teacher is the owner of the exam, and can delegate (grant) the contributor, 
// examinator and decoder role to other users. By default, the teacher role is
// assigned all other roles.
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
include "include/teacher/testcase.inc";
include "include/export.inc";
include "include/import.inc";
include "include/media.inc";

// 
// The index page:
// 
class ManagerPage extends TeacherPage
{

        private static $params = array(
                "exam"    => parent::pattern_index,
                "action"  => "/^(add|edit|show|copy|test|delete|cancel|finish|export|import)$/",
                "role"    => "/^(contributor|examinator|decoder)$/",
                "type"    => "/^(op)$/",
                "user"    => parent::pattern_index,
                "uuid"    => parent::pattern_user, // username
                "name"    => parent::pattern_name, // person name
                "unit"    => parent::pattern_textline, // organization unit
                "desc"    => parent::pattern_textarea, // exam description
                "grade"   => parent::pattern_textarea, // grades
                "details" => parent::pattern_textline,
                "start"   => parent::pattern_textline, // start date/time
                "end"     => parent::pattern_textline, // end date/time
                "order"   => "/^(state|name|date)$/"
        );

        public function __construct()
        {
                parent::__construct(_("Examination Management"), self::$params);
                if (!isset($this->param->order)) {
                        $this->param->order = "date";
                }
        }

        //
        // The main entry point. This is where all processing begins.
        //
        public function printBody()
        {
                echo "<h3>" . _("Examination Management") . "</h3>\n";

                //
                // Authorization first:
                //
                $this->checkAccess();

                //
                // Bussiness logic:
                //
                if (!isset($this->param->exam)) {
                        if (!isset($this->param->action)) {
                                $this->showAvailableExams();
                        } elseif ($this->param->action == "add") {
                                $this->addExam(isset($this->param->name));
                        } elseif ($this->param->action == "import") {
                                $this->importExam(isset($_FILES['file']));
                        }
                } else {
                        if (isset($this->param->action)) {
                                if (isset($this->param->role)) {
                                        if ($this->param->action == "delete") {
                                                $this->assert('user');
                                        }
                                        if ($this->param->role == "contributor") {
                                                if ($this->param->action == "add") {
                                                        $this->addContributor(isset($this->param->uuid));
                                                } elseif ($this->param->action == "delete") {
                                                        $this->deleteContributor();
                                                }
                                        } elseif ($this->param->role == "examinator") {
                                                if ($this->param->action == "add") {
                                                        $this->addExaminator(isset($this->param->uuid));
                                                } elseif ($this->param->action == "delete") {
                                                        $this->deleteExaminator();
                                                }
                                        } elseif ($this->param->role == "decoder") {
                                                if ($this->param->action == "add") {
                                                        $this->addDecoder(isset($this->param->uuid));
                                                } elseif ($this->param->action == "delete") {
                                                        $this->deleteDecoder();
                                                }
                                        }
                                } else {
                                        if ($this->param->action == "show") {
                                                $this->showExam();
                                        } elseif ($this->param->action == "edit") {
                                                $this->editExam(isset($this->param->name));
                                        } elseif ($this->param->action == "copy") {
                                                $this->copyExam();
                                        } elseif ($this->param->action == "test") {
                                                $this->testExam();
                                        } elseif ($this->param->action == "delete") {
                                                $this->deleteExam();
                                        } elseif ($this->param->action == "cancel") {
                                                $this->cancelExam();
                                        } elseif ($this->param->action == "finish") {
                                                $this->finishExam();
                                        } elseif ($this->param->action == "export") {
                                                $this->exportExam();
                                        }
                                }
                        } else {
                                $this->showExam();
                        }
                }
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
        // Common form for adding and editing exam properties.
        //
        private function showExamForm($exam, $data, $action, $readonly = false)
        {
                $grades = new ExamGrades($data->getExamGrades());

                $info = $this->manager->getInfo();
                $form = new Form("manager.php", "GET");
                $form->setName("form");
                $form->addHidden("action", $action);
                if ($exam != 0) {
                        $form->addHidden("exam", $exam);
                }

                //
                // Show common properties:
                //
                $form->addSectionHeader(_("Common Properties"));
                $input = $form->addTextBox("unit", $data->getExamOrgUnit());
                $input->setLabel(_("Organization"));
                $input->setSize(50);
                $input = $form->addTextBox("name", $data->getExamName());
                $input->setLabel(_("Name"));
                $input->setSize(50);
                if ($action == "add") {
                        $input->setEvent(EVENT_ON_DOUBLE_CLICK, EVENT_HANDLER_CLEAR_CONTENT);
                }
                $input = $form->addTextArea("desc", $data->getExamDescription());
                $input->setLabel(_("Description"));
                $input->setClass("description");
                if ($action == "add") {
                        $input->setEvent(EVENT_ON_DOUBLE_CLICK, EVENT_HANDLER_CLEAR_CONTENT);
                }

                //
                // Show scheduling section:
                //
                if ($this->manager->getExamID() == 0 || $info->isEditable()) {
                        $form->addSectionHeader(_("Scheduling"));
                        $input = $form->addTextBox("start", strftime(DATETIME_FORMAT, strtotime($data->getExamStartTime())));
                        $input->setLabel(_("Start time"));
                        $input->setSize(30);
                        $input->setid("stm");
                        $image = $form->addElement(new Image("../images/datetimepicker/cal.gif", _("Calendar icon")));
                        $image->setEvent(EVENT_ON_CLICK, "javascript:{NewCssCal('stm','yyyymmdd','dropdown',true)}");
                        $input = $form->addTextBox("end", strftime(DATETIME_FORMAT, strtotime($data->getExamEndTime())));
                        $input->setLabel(_("End time"));
                        $input->setSize(30);
                        $input->setid("etm");
                        $image = $form->addElement(new Image("../images/datetimepicker/cal.gif", _("Calendar icon")));
                        $image->setEvent(EVENT_ON_CLICK, "javascript:{NewCssCal('etm','yyyymmdd','dropdown',true)}");
                }

                //
                // Show graduation section:
                //
                $form->addSectionHeader(_("Graduation"));
                $input = $form->addTextArea("grade", $grades->getText());
                $input->setClass("grade");
                $input->setLabel();
                $input->setTitle(_("Input name:value pairs on separate lines defining the graduation levels on this examination. The first line must be on form name:0, denoting the failed grade."));

                //
                // Show details in result section:
                // 
                $details = $data->getExamDetails();

                $form->addSectionHeader(_("Result"));
                $input = $form->addCheckBox("details[]", RESULT_EXPOSE_EMPLOYEES, _("Expose responsible people"));
                $input->setTitle(_("Expose names and contact information for people involved in the creation and correction process in the result PDF seen by the student."));
                if ($details & RESULT_EXPOSE_EMPLOYEES) {
                        $input->setChecked();
                }
                $input->setLabel();

                $input = $form->addCheckBox("details[]", RESULT_OTHERS_STATISTIC, _("Include other students statistics"));
                $input->setTitle(_("Include statistics like avarage/mean values and score distribution compared to other students in the result PDF seen by the student."));
                if ($details & RESULT_OTHERS_STATISTIC) {
                        $input->setChecked();
                }
                $input->setLabel();

                if (!$readonly) {
                        $form->addSpace();
                        $button = $form->addSubmitButton("submit", _("Submit"));
                        $button->setLabel();
                }
                $form->output();
        }

        //
        // Add an new exam.
        //
        private function addExam($store)
        {
                if (!$store) {
                        printf("<p>" . _("Define the common properties of the exam. Click on the 'Submit' button to create this exam.") . "</p>\n");
                        $data = new DataRecord(array(
                                "examorgunit"     => $this->getOrganisationUnit(phpCAS::getUser()),
                                "examname"        => _("Name"),
                                "examdescription" => _("Description"),
                                "examgrades"      => json_encode(array(
                                        "U"  => 0,
                                        "G"  => 15,
                                        "VG" => 20)
                                ),
                                "examstarttime"   => DATETIME_NONE,
                                "examendtime"     => DATETIME_NONE,
                                "examdetails"     => RESULT_DETAILS_DEFAULT)
                        );
                        $this->manager = new Manager(0);
                        $this->showExamForm(0, $data, "add");
                } else {
                        $gd = new ExamGrades();
                        $gd->setText($this->param->grade);

                        $dd = new ExamDetails($this->param->details);

                        $this->manager = new Manager(0);
                        $this->manager->setData(
                            $this->param->unit, $this->param->name, $this->param->desc, $gd->encode(), $dd->getMask(), strtotime($this->param->start), strtotime($this->param->end));

                        //
                        // By default, add creator of the exam as contributor and decoder.
                        //
                        $this->manager->addContributor(phpCAS::getUser());
                        $this->manager->addDecoder(phpCAS::getUser());
                        header(sprintf("location: manager.php?exam=%d", $this->manager->getExamID()));
                }
        }

        //
        // Edit an existing exam.
        //
        private function editExam($store)
        {
                $data = $this->manager->getData();

                if (!$store) {
                        printf("<p>" . _("This page let you edit common properties of the exam. Click on the 'Submit' button to save changes.") . "</p>\n");
                        $this->showExamForm($this->param->exam, $data, "edit");
                } else {
                        $gd = new ExamGrades();
                        $gd->setText($this->param->grade);

                        $dd = new ExamDetails($this->param->details);

                        if (!isset($this->param->start)) {
                                $this->param->start = $data->getExamStartTime();
                        }
                        if (!isset($this->param->end)) {
                                $this->param->end = $data->getExamEndTime();
                        }

                        $this->manager->setData(
                            $this->param->unit, $this->param->name, $this->param->desc, $gd->encode(), $dd->getMask(), strtotime($this->param->start), strtotime($this->param->end)
                        );
                        header(sprintf("location: manager.php?exam=%d", $this->param->exam));
                }
        }

        //
        // Creates a copy of the current exam. The contributor, examinator and decoder is
        // roles are preserved (but re-associated with the copy). The list of questions
        // is also preserved, but without any associated answers.
        //
        private function copyExam()
        {
                $copy = $this->manager->copy();
                header(sprintf("location: manager.php?exam=%d&action=edit", $copy->getExamID()));
        }

        //
        // Create a test case of the exam and redirect user to it. This is also
        // known as dry-run, in that the original examination remains unmodified.
        //
        private function testExam()
        {
                $test = new TestCase($this->param->exam);
                $test->create();
                header(sprintf("location: ../exam/index.php?exam=%d", $test->getExamID()));
        }

        private function deleteExam()
        {
                $this->manager->delete();
                header("location: manager.php");
        }

        //
        // Cancel test case of exam.
        //
        private function cancelExam()
        {
                $test = new TestCase($this->param->exam);
                $test->delete();
                header("location: manager.php");
        }

        //
        // Finish test case of exam.
        //
        private function finishExam()
        {
                $test = new TestCase($this->param->exam);
                $test->finish();
                header(sprintf("location: manager.php?exam=%d", $this->param->exam));
        }

        //
        // Export complete exam.
        //
        private function exportExam()
        {
                header(sprintf("location: export.php?exam=%d", $this->param->exam));
                exit(0);
        }

        //
        // Import complete exam.
        //
        private function importExam($store)
        {
                if ($store) {
                        try {
                                $importer = FileImport::getReader(
                                        $this->param->type, $_FILES['file']['name'], $_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['size']
                                );
                                $importer->open();
                                $this->param->exam = $importer->read(0, Database::getConnection(), OPENEXAM_IMPORT_INCLUDE_ALL);
                                $importer->close();
                        } catch (ImportException $exception) {
                                $this->fatal(_("Failed Import Examination"), $exception->getMessage());
                        }
                        header(sprintf("location: manager.php?exam=%d", $this->param->exam));
                } else {
                        printf("<p>" .
                            _("This page let you create a new examination from an OpenExam project file exported earlier. ") .
                            _("Browse your local disk to select an file containing the OpenExam project to import. ") .
                            "</p>\n");

                        $form = new Form("manager.php", "POST");
                        $form->setEncodingType("multipart/form-data");
                        $form->addHidden("action", "import");
                        $form->addHidden("type", "op");
                        $form->addHidden("MAX_FILE_SIZE", 500000);
                        $input = $form->addFileInput("file");
                        $input->setLabel(_("Filename"));
                        $input = $form->addSubmitButton("import", _("Import"));
                        $form->output();
                }
        }

        //
        // Show properties for this exam.
        //
        private function showExam()
        {
                $data = $this->manager->getData();
                $info = $this->manager->getInfo();

                //
                // Build the root node:
                //
                $tree = new TreeBuilder($data->getExamName());
                $root = $tree->getRoot();
                if ($this->roles->getManagerRoles() > 0) {
                        $root->addLink(_("Export"), sprintf("?exam=%d&amp;action=export", $data->getExamID()), _("Export this examination including its questions as XML data."));
                        $root->addLink(_("Copy"), sprintf("?exam=%d&amp;action=copy", $data->getExamID()), _("Create a new examination by using this examination as a template."));
                        $root->addLink(_("Edit"), sprintf("?exam=%d&amp;action=edit", $data->getExamID()), _("Edit common properties like name, description or grades for this examination."));   // Should be limited
                }

                //
                // Build the contributors node:
                //
                $child = $root->addChild(_("Contributors"));
                if ($info->isContributable()) {
                        $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add&amp;role=contributor", $data->getExamID()), _("Add a question contributor to this examination."));
                }
                $contributors = $this->manager->getContributors();
                foreach ($contributors as $contributor) {
                        $subobj = $child->addChild($this->getFormatName($contributor->getContributorUser()));
                        if ($info->isContributable()) {
                                $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=contributor&amp;user=%d", $contributor->getExamID(), $contributor->getContributorID()), sprintf(_("Remove %s as a question contributor for this examination."), $this->getFormatName($contributor->getContributorUser())));
                        }
                }

                //
                // Build the examinators node:
                //
                $child = $root->addChild(_("Examinators"));
                if ($info->isExaminatable()) {
                        $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add&amp;role=examinator", $data->getExamID()), _("Add a person with the examinator role."));
                }
                $examinators = $this->manager->getExaminators();
                foreach ($examinators as $examinator) {
                        $subobj = $child->addChild($this->getFormatName($examinator->getExaminatorUser()));
                        if ($info->isExaminatable()) {
                                $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=examinator&amp;user=%d", $examinator->getExamID(), $examinator->getExaminatorID()), sprintf(_("Remove %s as an examinator for this examination."), $this->getFormatName($examinator->getExaminatorUser())));
                        }
                }

                //
                // Build the decoders node:
                //
                $child = $root->addChild(_("Decoders"));
                $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add&amp;role=decoder", $data->getExamID()), _("Add a person with the examinator role."));
                $decoders = $this->manager->getDecoders();
                foreach ($decoders as $decoder) {
                        $subobj = $child->addChild($this->getFormatName($decoder->getDecoderUser()));
                        $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=decoder&amp;user=%d", $decoder->getExamID(), $decoder->getDecoderID()), sprintf(_("Remove %s as a decoder for this examination."), $this->getFormatName($decoder->getDecoderUser())));
                }

                //
                // Build the questions node:
                //
                $quest = $root->addChild(_("Questions"));
                if ($this->manager->isContributor(phpCAS::getUser())) {
                        if ($info->isContributable()) {
                                $quest->addLink(_("Add"), sprintf("contribute.php?exam=%d&amp;action=add", $data->getExamID()), _("Add a new question for this examination."));
                                $quest->addLink(_("Import"), sprintf("contribute.php?exam=%d&amp;action=import", $data->getExamID()), _("Import questions from file on disk."));
                                $quest->addLink(_("Remove all"), sprintf("contribute.php?exam=%d&amp;action=delete&amp;question=all", $data->getExamID()), _("Remove all questions from this examination."));
                        }
                        $quest->addLink(_("Show"), sprintf("contribute.php?exam=%d", $data->getExamID()), _("Open the page showing all questions at once, where questions can be edited and previewed."));
                }

                $child = $quest->addChild(_("Active"));
                $questions = $this->manager->getQuestions('active');
                foreach ($questions as $question) {
                        $subobj = $child->addChild(sprintf("%s: <i>%s</i>", $question->getQuestionName(), strip_tags($question->getQuestionText())));

                        if ($this->manager->isContributor(phpCAS::getUser())) {
                                if (!$info->isDecoded()) {
                                        $subobj->addLink(_("Edit"), sprintf("contribute.php?exam=%d&amp;action=edit&amp;question=%d", $question->getExamID(), $question->getQuestionID()), _("Edit properties for this question"));
                                }
                                if ($info->isContributable()) {
                                        $subobj->addLink(_("Delete"), sprintf("contribute.php?exam=%d&amp;action=delete&amp;question=%d", $question->getExamID(), $question->getQuestionID()), _("Permanent delete this question"));
                                }
                                $subobj->addLink(_("Remove"), sprintf("contribute.php?exam=%d&amp;action=remove&amp;question=%d", $question->getExamID(), $question->getQuestionID()), _("Flag this question as removed (not deleted permanent). Can later be restored from the removed list below."));
                        }
                }
                $child = $quest->addChild(_("Removed"));
                $questions = $this->manager->getQuestions('removed');
                foreach ($questions as $question) {
                        $subobj = $child->addChild(sprintf("%s %s...", $question->getQuestionName(), substr($question->getQuestionText(), 0, 60)));
                        if ($this->manager->isContributor(phpCAS::getUser())) {
                                $subobj->addLink(_("Restore"), sprintf("contribute.php?exam=%d&amp;action=restore&amp;question=%d", $question->getExamID(), $question->getQuestionID()), _("Flag this question as active again"));
                        }
                }

                // 
                // Build the resource node. This node contains links to common
                // resources, like databases and equation papers.
                // 
                $media = new MediaLibrary($this->param->exam);
                $child = $root->addChild(_("Resources"));
                if (!$info->isFinished()) {
                        $child->addLink(_("Add"), sprintf("../media/index.php?exam=%d&action=add&type=resource", $this->param->exam));
                }
                foreach ($media->resource as $file) {
                        $subobj = $child->addChild($file->name);
                        if (!$info->isFinished()) {
                                $subobj->addLink(_("Show"), $file->url, _("Show resource content"));
                                $subobj->addLink(_("Delete"), sprintf("../media/index.php?exam=%d&action=delete&type=resource&file=%s", $this->param->exam, $file->name));
                        }
                }

                printf("<p>" .
                    _("This page let you add/delete contributors, examinators, decoders and questions from this exam. ") .
                    _("Not all options might be available, i.e. its not possible to add questions to an already started examination.") .
                    "</p>\n");
                printf("<p>" .
                    _("For anonymity integrity reasons, people with the contributor role should not have the examinator role assigned on the same examination.") .
                    "</p>\n");

                $tree->output();
        }

        //
        // Helper function for assigning roles to users for this exam. The text
        // parameter is the description text to show.
        //
        private function addExamRole($role, $text)
        {
                printf("<p>%s</p>\n", $text);
                $form = new Form("manager.php", "GET");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("role", $role);
                $form->addHidden("action", "add");
                $input = $form->addTextBox("uuid");
                $input->setLabel(_("UU-ID"));
                $form->addSubmitButton("submit", _("Add"));
                $form->output();
        }

        private function addContributor($store)
        {
                if (!$store) {
                        $data = $this->manager->getData();
                        $text = sprintf(_("Allow this user to contribute questions for the examination '%s' by granting he/she the 'contribute' role."), $data->getExamName());
                        return $this->addExamRole("contributor", $text);
                }

                $this->manager->addContributor($this->param->uuid);
                header(sprintf("location: manager.php?exam=%d&action=show", $this->param->exam));
        }

        private function deleteContributor()
        {
                $this->manager->deleteContributor($this->param->user);
                header(sprintf("location: manager.php?exam=%d", $this->param->exam));
        }

        private function addExaminator($store)
        {
                if (!$store) {
                        $data = $this->manager->getData();
                        $text = sprintf(_("Allow this user to add students for the examination '%s' by granting he/she the 'examinator' role."), $data->getExamName());
                        return $this->addExamRole("examinator", $text);
                }

                $this->manager->addExaminator($this->param->uuid);
                header(sprintf("location: manager.php?exam=%d&action=show", $this->param->exam));
        }

        private function deleteExaminator()
        {
                $this->manager->deleteExaminator($this->param->user);
                header(sprintf("location: manager.php?exam=%d", $this->param->exam));
        }

        private function addDecoder($store)
        {
                if (!$store) {
                        $data = $this->manager->getData();
                        $text = sprintf(_("Allow this user to decode the real identity behind the students assigned for the examination '%s' by granting he/she the 'decoder' role."), $data->getExamName());
                        return $this->addExamRole("decoder", $text);
                }

                $this->manager->addDecoder($this->param->uuid);
                header(sprintf("location: manager.php?exam=%d&action=show", $this->param->exam));
        }

        private function deleteDecoder()
        {
                $this->manager->deleteDecoder($this->param->user);
                header(sprintf("location: manager.php?exam=%d", $this->param->exam));
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

$page = new ManagerPage();
$page->render();

?>
