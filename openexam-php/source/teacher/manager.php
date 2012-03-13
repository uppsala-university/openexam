<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
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

// 
// Maximum length of question text before its trunkated in the list.
//
if (!defined("MANAGER_QUESTION_MAXLEN")) {
        define("MANAGER_QUESTION_MAXLEN", 60);
}
if (!defined("MANAGER_QUESTION_FORMAT")) {
        define("MANAGER_QUESTION_FORMAT", "%s: <i>%s</i>");
}

// 
// The index page:
// 
class ManagerPage extends TeacherPage
{

        private static $params = array(
                "exam"   => "/^\d+$/",
                "action" => "/^(add|edit|show|copy|test|delete|cancel|finish|export|import)$/",
                "role"   => "/^(contributor|examinator|decoder)$/",
                "type"   => "/^(op)$/",
                "user"   => "/^\d+$/",
                "uuid"   => "/^[0-9a-zA-Z]{1,10}$/",
                "name"   => "/^(\p{L}|\p{N}|\p{Z}|\p{P})+$/u"
        );

        public function __construct()
        {
                parent::__construct(_("Examination Management"), self::$params);
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
                self::checkAccess();

                //
                // Bussiness logic:
                //
                if (!isset($_REQUEST['exam'])) {
                        if (!isset($_REQUEST['action'])) {
                                self::showAvailableExams();
                        } elseif ($_REQUEST['action'] == "add") {
                                self::addExam(isset($_REQUEST['name']));
                        } elseif ($_REQUEST['action'] == "import") {
                                self::importExam(isset($_FILES['file']));
                        }
                } else {
                        if (isset($_REQUEST['action'])) {
                                if (isset($_REQUEST['role'])) {
                                        if ($_REQUEST['action'] == "delete") {
                                                self::assert('user');
                                        }
                                        if ($_REQUEST['role'] == "contributor") {
                                                if ($_REQUEST['action'] == "add") {
                                                        self::addContributor(isset($_REQUEST['uuid']));
                                                } elseif ($_REQUEST['action'] == "delete") {
                                                        self::deleteContributor();
                                                }
                                        } elseif ($_REQUEST['role'] == "examinator") {
                                                if ($_REQUEST['action'] == "add") {
                                                        self::addExaminator(isset($_REQUEST['uuid']));
                                                } elseif ($_REQUEST['action'] == "delete") {
                                                        self::deleteExaminator();
                                                }
                                        } elseif ($_REQUEST['role'] == "decoder") {
                                                if ($_REQUEST['action'] == "add") {
                                                        self::addDecoder(isset($_REQUEST['uuid']));
                                                } elseif ($_REQUEST['action'] == "delete") {
                                                        self::deleteDecoder();
                                                }
                                        }
                                } else {
                                        if ($_REQUEST['action'] == "show") {
                                                self::showExam();
                                        } elseif ($_REQUEST['action'] == "edit") {
                                                self::editExam(isset($_REQUEST['name']));
                                        } elseif ($_REQUEST['action'] == "copy") {
                                                self::copyExam();
                                        } elseif ($_REQUEST['action'] == "test") {
                                                self::testExam();
                                        } elseif ($_REQUEST['action'] == "delete") {
                                                self::deleteExam();
                                        } elseif ($_REQUEST['action'] == "cancel") {
                                                self::cancelExam();
                                        } elseif ($_REQUEST['action'] == "finish") {
                                                self::finishExam();
                                        } elseif ($_REQUEST['action'] == "export") {
                                                self::exportExam();
                                        }
                                }
                        } else {
                                self::showExam();
                        }
                }
        }

        //
        // Show all exams the current user is the owner of.
        //
        private function showAvailableExams()
        {
                printf("<p>" .
                    _("This page let you create new exams or manage your old ones. ") .
                    _("These are the exams you are the manager of: ") .
                    "</p>\n");

                $tree = new TreeBuilder(_("Examinations"));
                $root = $tree->getRoot();
                if ($this->roles->getManagerRoles() > 0) {
                        $root->addLink(_("Import"), "?action=import", _("Creates a new examination by importing an OpenExam project."));
                        $root->addLink(_("Add"), "?action=add", _("Creates a new examination."));
                }

                //
                // Group the examinations by their state:
                //
                $exams = Manager::getExams(phpCAS::getUser());
                $nodes = array(
                        'u' => array(
                                'name' => _("Upcoming"),
                                'data' => array()
                        ),
                        'a' => array(
                                'name' => _("Active"),
                                'data' => array()
                        ),
                        'f' => array(
                                'name' => _("Finished"),
                                'data' => array()
                        ),
                        't' => array(
                                'name' => _("Testing"),
                                'data' => array()
                        )
                );

                foreach ($exams as $exam) {
                        $manager = new Manager($exam->getExamID());
                        $state = $manager->getInfo();
                        if ($state->isTestCase()) {
                                $nodes['t']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isUpcoming()) {
                                $nodes['u']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isRunning()) {
                                $nodes['a']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isFinished()) {
                                $nodes['f']['data'][] = array($exam->getExamName(), $state);
                        }
                }

                foreach ($nodes as $type => $group) {
                        if (count($group['data']) > 0) {
                                $node = $root->addChild($group['name']);
                                foreach ($group['data'] as $data) {
                                        $name = $data[0];
                                        $state = $data[1];
                                        $child = $node->addChild($name);
                                        $child->setLink(sprintf("?exam=%d&amp;action=show", $state->getInfo()->getExamID()));
                                        $child->addLink(_("Export"), sprintf("?exam=%d&amp;action=export", $state->getInfo()->getExamID()), _("Export this examination including its questions as XML data."));
                                        if ($this->roles->getManagerRoles() > 0) {
                                                $child->addLink(_("Copy"), sprintf("?exam=%d&amp;action=copy", $state->getInfo()->getExamID()), _("Create a new examination by using this examination as a template."));
                                                $child->addLink(_("Edit"), sprintf("?exam=%d&amp;action=edit", $state->getInfo()->getExamID()), _("Edit common properties like name, description or grades for this examination."));
                                        }
                                        if (!$state->hasAnswers()) {
                                                $child->addLink(_("Delete"), sprintf("?exam=%d&amp;action=delete", $state->getInfo()->getExamID()), _("Deletes the examination along with any questions."), array(EVENT_ON_CLICK => EVENT_HANDLER_CONFIRM_DELETE));
                                        } elseif ($state->isTestCase()) {
                                                $child->addLink(_("Delete"), sprintf("?exam=%d&amp;action=cancel", $state->getInfo()->getExamID()), _("Deletes the examination along with any questions."));
                                        }
                                        if (!$state->isTestCase() && $state->isUpcoming()) {
                                                $child->addLink(_("Test"), sprintf("?exam=%d&amp;action=test", $state->getInfo()->getExamID()), _("Test this examination by creating and opening a copy."), array("target" => "_blank"));
                                        }
                                        if ($state->isTestCase() && !$state->isFinished()) {
                                                $child->addLink(_("Test"), sprintf("../exam/index.php?exam=%d", $state->getInfo()->getExamID()), _("Run this test case examination."), array("target" => "_blank"));
                                        }
                                        $child->addChild(sprintf("%s: %s", _("Created"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamCreated()))));
                                        $sdate = strtotime($state->getInfo()->getExamStartTime());
                                        $edate = strtotime($state->getInfo()->getExamEndTime());
                                        if (date('Ymd', $sdate) == date('Ymd', $edate)) {
                                                $child->addChild(sprintf("%s: %s %s - %s", _("Occasion"), strftime(DATE_FORMAT, $sdate), strftime(TIME_FORMAT, $sdate), strftime(TIME_FORMAT, $edate)));
                                        } else {
                                                $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, $sdate)));
                                                $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, $edate)));
                                        }
                                }
                        }
                }

                $tree->output();
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
                                            "U"             => 0,
                                            "G"             => 15,
                                            "VG"            => 20)
                                    ),
                                    "examstarttime" => DATETIME_NONE,
                                    "examendtime"   => DATETIME_NONE,
                                    "examdetails"   => RESULT_DETAILS_DEFAULT)
                        );
                        $this->manager = new Manager(0);
                        self::showExamForm(0, $data, "add");
                } else {
                        $gd = new ExamGrades();
                        $gd->setText($_REQUEST['grade']);

                        $dd = new ExamDetails($_REQUEST['details']);

                        $this->manager = new Manager(0);
                        $this->manager->setData(
                            $_REQUEST['unit'], $_REQUEST['name'], $_REQUEST['desc'], $gd->encode(), $dd->getMask(), strtotime($_REQUEST['start']), strtotime($_REQUEST['end'])
                        );

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
                        self::showExamForm($this->param->exam, $data, "edit");
                } else {
                        $gd = new ExamGrades();
                        $gd->setText($_REQUEST['grade']);

                        $dd = new ExamDetails($_REQUEST['details']);

                        if (!isset($_REQUEST['start'])) {
                                $_REQUEST['start'] = $data->getExamStartTime();
                        }
                        if (!isset($_REQUEST['end'])) {
                                $_REQUEST['end'] = $data->getExamEndTime();
                        }

                        $this->manager->setData(
                            $_REQUEST['unit'], $_REQUEST['name'], $_REQUEST['desc'], $gd->encode(), $dd->getMask(), strtotime($_REQUEST['start']), strtotime($_REQUEST['end'])
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
                $exporter = new Export($this->param->exam);
                $exporter->send();
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
                                $this->param->exam = $importer->read(0, Database::getConnection());
                                $importer->close();
                        } catch (ImportException $exception) {
                                ErrorPage::show(_("Failed Import Questions"), $exception->getMessage());
                                exit(1);
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
                                $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=contributor&amp;user=%d", $contributor->getExamID(), $contributor->getContributorID()), sprintf(_("Remove %s as a question contributor for this examination."), $this->getCommonName($contributor->getContributorUser())));
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
                                $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=examinator&amp;user=%d", $examinator->getExamID(), $examinator->getExaminatorID()), sprintf(_("Remove %s as an examinator for this examination."), $this->getCommonName($examinator->getExaminatorUser())));
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
                        $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=decoder&amp;user=%d", $decoder->getExamID(), $decoder->getDecoderID()), sprintf(_("Remove %s as a decoder for this examination."), $this->getCommonName($decoder->getDecoderUser())));
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
                        if (strlen($question->getQuestionText()) > MANAGER_QUESTION_MAXLEN) {
                                $format = sprintf("%s...", MANAGER_QUESTION_FORMAT);
                                $subobj = $child->addChild(sprintf($format, $question->getQuestionName(), substr(strip_tags($question->getQuestionText()), 0, MANAGER_QUESTION_MAXLEN)));
                        } else {
                                $format = MANAGER_QUESTION_FORMAT;
                                $subobj = $child->addChild(sprintf($format, $question->getQuestionName(), strip_tags($question->getQuestionText())));
                        }
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
                        return self::addExamRole("contributor", $text);
                }

                $this->manager->addContributor($_REQUEST['uuid']);
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
                        return self::addExamRole("examinator", $text);
                }

                $this->manager->addExaminator($_REQUEST['uuid']);
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
                        return self::addExamRole("decoder", $text);
                }

                $this->manager->addDecoder($_REQUEST['uuid']);
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
                                ErrorPage::show(_("Access denied!"), sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), "creator"));
                                exit(1);
                        }
                } else {
                        if ($this->roles->getCreatorRoles() == 0 && $this->roles->getManagerRoles() == 0) {
                                ErrorPage::show(_("Access denied!"), _("Only users granted the teacher role or being the creator on at least one exam can access this page. The script processing has halted."));
                                exit(1);
                        }
                }
        }

}

$page = new ManagerPage();
$page->render();
?>
