<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/examinator.php
// Author: Anders Lövgren
// Date:   2010-05-04
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
include "include/uppdok.inc";

// 
// Business logic:
// 
include "include/teacher.inc";
include "include/teacher/manager.inc";
include "include/teacher/examinator.inc";

if (!defined("EXAMINATOR_VISIBLE_IDENTITIES")) {
        define("EXAMINATOR_VISIBLE_IDENTITIES", true);
}
if (!defined("EXAMINATOR_TERMIN_VT")) {
        define("EXAMINATOR_TERMIN_VT", 1);
}
if (!defined("EXAMINATOR_TERMIN_HT")) {
        define("EXAMINATOR_TERMIN_HT", 2);
}
if (!defined("EXAMINATOR_YEAR_HISTORY")) {
        define("EXAMINATOR_YEAR_HISTORY", 5);
}

// 
// The examinator page:
// 
class ExaminatorPage extends TeacherPage
{

        private $params = array(
                "exam" => "/^\d+$/",
                "what" => "/^(user|users|course)$/",
                "code" => "/^([0-9a-fA-F]{1,15}|)$/",
                "user" => "/^[0-9a-zA-Z]{1,10}$/",
                "users" => "/.*/",
                "course" => "/^[0-9a-zA-Z]{1,10}$/",
                "stime" => "/^.*$/",
                "etime" => "/^.*$/",
                "action" => "/^(add|edit|show|delete)$/",
                "year" => "/^[0-9]{4}$/",
                "termin" => "/^[1-2]$/"
        );

        public function __construct()
        {
                parent::__construct(_("Examinator Page"), $this->params);
        }

        //
        // The main entry point. This is where all processing begins.
        //
        public function printBody()
        {
                //
                // Authorization first:
                //
                if (isset($_REQUEST['exam'])) {
                        self::checkAccess();
                }

                //
                // Bussiness logic:
                //
                if (isset($_REQUEST['exam'])) {
                        if (!isset($_REQUEST['action'])) {
                                $_REQUEST['action'] = "show";
                        }
                        if ($_REQUEST['action'] == "add") {
                                if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
                                        if (isset($_REQUEST['what'])) {
                                                if ($_REQUEST['what'] == "user") {
                                                        self::assert(array('user', 'code'));
                                                        self::saveAddStudent();
                                                } elseif ($_REQUEST['what'] == "users") {
                                                        self::assert('users');
                                                        self::saveAddStudents();
                                                } elseif ($_REQUEST['what'] == "course") {
                                                        self::assert('course');
                                                        self::assert('year');
                                                        self::assert('termin');
                                                        self::saveAddCourse();
                                                } else {
                                                        self::formAddStudents($_REQUEST['what']);
                                                }
                                        }
                                } else {
                                        if (!isset($_REQUEST['what'])) {
                                                $_REQUEST['what'] = "course";
                                        }
                                        self::formAddStudents($_REQUEST['what']);
                                }
                        } elseif ($_REQUEST['action'] == "edit") {
                                if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
                                        self::assert(array('stime', 'etime'));
                                        self::saveEditSchedule();
                                } else {
                                        self::formEditSchedule();
                                }
                        } elseif ($_REQUEST['action'] == "show") {
                                self::showExam($_REQUEST['exam']);
                        } elseif ($_REQUEST['action'] == "delete") {
                                self::assert('user');
                                self::deleteStudent();
                        }
                } else {
                        self::showAvailableExams();
                }
        }

        //
        // Verify that the caller has been granted the required role on this exam.
        //
        private function checkAccess()
        {
                if (!$this->manager->isExaminator(phpCAS::getUser())) {
                        ErrorPage::show(_("Access denied!"), sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), "examinator"));
                        exit(1);
                }
        }

        //
        // Show the form for rescheduling the exam.
        //
        private function formEditSchedule()
        {
                $data = $this->manager->getData();

                printf("<h3>" . _("Reschedule Examination") . "</h3>\n");
                printf("<p>" . _("This page let you reschedule the start and end time of the examination.") . "</p>\n");

                $form = new Form("examinator.php", "GET");
                $form->setName("form");
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("mode", "save");
                $form->addHidden("action", "edit");
                $input = $form->addTextBox("stime", strftime(DATETIME_FORMAT, strtotime($data->getExamStartTime())));
                $input->setLabel(_("Starts"));
                $input->setSize(25);
                $input->setid("stm");
                $image = $form->addElement(new Image("../images/datetimepicker/cal.gif", _("Calendar icon")));
                $image->setEvent(EVENT_ON_CLICK, "javascript:{NewCssCal('stm','yyyymmdd','dropdown',true)}");
                $input = $form->addTextBox("etime", strftime(DATETIME_FORMAT, strtotime($data->getExamEndTime())));
                $input->setLabel(_("Ends"));
                $input->setSize(25);
                $input->setid("etm");
                $image = $form->addElement(new Image("../images/datetimepicker/cal.gif", _("Calendar icon")));
                $image->setEvent(EVENT_ON_CLICK, "javascript:{NewCssCal('etm','yyyymmdd','dropdown',true)}");
                $form->addSpace();
                $input = $form->addSubmitButton("submit", _("Submit"));
                $input->setLabel();
                $form->output();
        }

        //
        // Save rescheduled start and end time for this exam.
        //
        private function saveEditSchedule()
        {
                $handler = new Examinator($this->param->exam);
                $handler->setSchedule(strtotime($this->param->stime), strtotime($this->param->etime));

                header(sprintf("location: examinator.php?exam=%d", $this->param->exam));
        }

        //
        // Show form for adding student(s).
        //
        private function formAddStudents($what)
        {
                printf("<h3>" . _("Add Students") . "</h3>\n");
                printf("<p>" . _("You can add students one by one or as a list of codes/student ID pairs. The list should be a newline separated list of username/code pairs where the username and code is separated by tabs.") . "</p>\n");
                printf("<p>" . _("If the student code is missing, then the system is going to generate a random code.") . "</p>\n");

                $mode = array(
                        "user" => _("Single"),
                        "users" => _("List"),
                        "course" => _("Import")
                );
                $disp = array();
                printf("<span class=\"links viewmode\">\n");
                foreach ($mode as $name => $text) {
                        if ($what != $name) {
                                $disp[] = sprintf("<a href=\"?exam=%d&amp;action=add&amp;what=%s\">%s</a>",
                                                $this->param->exam, $name, $text);
                        } else {
                                $disp[] = $text;
                        }
                }
                printf("%s: %s\n", _("Add"), implode(", ", $disp));
                printf("</span>\n");
                
                if ($what == "user") {
                        $form = new Form("examinator.php", "GET");
                        $form->addSectionHeader(_("Add student one by one:"));
                        $form->addHidden("exam", $this->param->exam);
                        $form->addHidden("mode", "save");
                        $form->addHidden("what", "user");
                        $form->addHidden("action", "add");
                        $input = $form->addTextBox("code");
                        $input->setLabel(_("Code"));
                        $input->setTitle(_("The anonymous code associated with this student logon."));
                        $input = $form->addTextBox("user");
                        $input->setLabel(_("UU-ID"));
                        $input->setTitle(_("The student logon username."));
                        $form->addSpace();
                        $input = $form->addSubmitButton("submit", _("Submit"));
                        $input->setLabel();
                        $form->output();
                } elseif ($what == "course") {
                        $form = new Form("examinator.php", "POST");
                        $form->addSectionHeader(_("Import from UPPDOK"));
                        $form->addHidden("exam", $this->param->exam);
                        $form->addHidden("mode", "save");
                        $form->addHidden("what", "course");
                        $form->addHidden("action", "add");
                        $input = $form->addTextBox("course");
                        $input->setLabel(_("Course Code"));
                        $input->setTitle(_("The UPPDOK course code (i.e. 1AB234) to import a list of students from."));
                        $combo = $form->addComboBox("year");
                        $combo->addOption(UppdokData::getCurrentYear(), _("Current"));
                        for ($y = 0; $y < EXAMINATOR_YEAR_HISTORY; $y++) {
                                $year = date('Y') - $y;
                                $combo->addOption($year, $year);
                        }
                        $combo->setLabel(_("Year"));
                        $combo = $form->addComboBox("termin");
                        $combo->addOption(UppdokData::getCurrentSemester(), _("Current"));
                        $combo->addOption(EXAMINATOR_TERMIN_VT, _("VT"));
                        $combo->addOption(EXAMINATOR_TERMIN_HT, _("HT"));
                        $combo->setLabel(_("Semester"));
                        $form->addSpace();
                        $input = $form->addSubmitButton("submit", _("Submit"));
                        $input->setLabel();
                        $form->output();
                } else {
                        $form = new Form("examinator.php", "POST");
                        $form->addSectionHeader(_("Add list of students:"));
                        $form->addHidden("exam", $this->param->exam);
                        $form->addHidden("mode", "save");
                        $form->addHidden("what", "users");
                        $form->addHidden("action", "add");
                        $input = $form->addTextArea("users", "user1\tcode1\nuser2\tcode2\n");
                        $input->setLabel(_("Students"));
                        $input->setTitle(_("Double-click inside the textarea to clear its content."));
                        $input->setEvent(EVENT_ON_DOUBLE_CLICK, EVENT_HANDLER_CLEAR_CONTENT);
                        $input->setClass("students");
                        $form->addSpace();
                        $input = $form->addSubmitButton("submit", _("Submit"));
                        $input->setLabel();
                        $form->output();
                }
        }

        //
        // Save a single student.
        //
        private function saveAddStudent()
        {
                $handler = new Examinator($this->param->exam);
                $handler->addStudent($this->param->user, $this->param->code);
                header(sprintf("location: examinator.php?exam=%d", $this->param->exam));
        }

        //
        // Save a list of students.
        //
        private function saveAddStudents()
        {
                $users = explode("\n", trim($this->param->users));
                foreach ($users as $row) {
                        if (($line = trim($row)) != "") {
                                if (strstr($line, "\t")) {
                                        list($user, $code) = explode("\t", trim($row));
                                        $data[$user] = $code;
                                } else {
                                        $data[$line] = null;
                                }
                        }
                }

                $handler = new Examinator($this->param->exam);
                $handler->addStudents($data);
                header(sprintf("location: examinator.php?exam=%d", $this->param->exam));
        }

        //
        // Save list of students by querying group membership in a directory
        // service.
        //
        private function saveAddCourse()
        {
                $uppdok = new UppdokData($this->param->year, $this->param->termin);
                $users = $uppdok->members($this->param->course);

                if (count($users) > 0) {
                        $data = array_combine($users, array_fill(0, count($users), null));

                        $handler = new Examinator($this->param->exam);
                        $handler->addStudents($data);
                } else {
                        ErrorPage::show(_("No members found"), sprintf(_("The query for course %s in the directory service returned an empty list. ") .
                                        _("It looks like no students belongs to this course."), $this->param->course));
                        exit(1);
                }

                header(sprintf("location: examinator.php?exam=%d", $this->param->exam));
        }

        //
        // Delete this student. The user argument is the numeric ID of the student.
        //
        private function deleteStudent()
        {
                $handler = new Examinator($this->param->exam);
                $handler->removeStudent($this->param->user);
                header(sprintf("location: examinator.php?exam=%d", $this->param->exam));
        }

        //
        // Show this single exam.
        //
        private function showExam()
        {
                $data = $this->manager->getData();
                $info = $this->manager->getInfo();

                $handler = new Examinator($this->param->exam);
                $students = $handler->getStudents();

                printf("<h3>" . _("Showing Examination") . "</h3>\n");
                printf("<p>" .
                        _("Showing details for the examination <i>'%s'</i>. ") .
                        _("Use the links to add and remove students, and reschedule the start and end time of the examination. ") .
                        "</p>\n", $data->getExamDescription());

                if (!EXAMINATOR_VISIBLE_IDENTITIES) {
                        printf("<p><img src=\"../icons/nuvola/info.png\" /> " . _("No usernames will be exposed unless the examination has already been decoded.") . "</p>\n");
                }

                $tree = new TreeBuilder($data->getExamName());
                $root = $tree->getRoot();
                $child = $root->addChild(_("Properties:"));
                $stobj = $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($data->getExamStartTime()))));
                $etobj = $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($data->getExamEndTime()))));
                $child->addChild(sprintf("%s: %s", _("Creator"), $this->getFormatName($data->getExamCreator())));
                $child->addChild(sprintf("%s: %d", _("Students"), $students->count()));

                if ($info->isExaminatable()) {
                        $stobj->addLink(_("Change"), sprintf("?exam=%d&amp;action=edit", $data->getExamID()), _("Click on this link to reschedule the examination"));
                        $etobj->addLink(_("Change"), sprintf("?exam=%d&amp;action=edit", $data->getExamID()), _("Click on this link to reschedule the examination"));
                }

                $child = $root->addChild(_("Students"));
                if ($info->isExaminatable()) {
                        $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add", $data->getExamID()), _("Click on this link to add students to this examination"));
                }
                if ($students->count() > 0) {
                        foreach ($students as $student) {
                                if ($info->isDecoded() || EXAMINATOR_VISIBLE_IDENTITIES) {
                                        $student->setStudentName($this->getFormatName($student->getStudentUser()));
                                } else {
                                        $student->setStudentName("xxx");
                                }
                                $subobj = $child->addChild(sprintf("<code>%s -> %s</code>", $student->getStudentCode(), $student->getStudentName()));
                                if ($info->isExaminatable()) {
                                        $subobj->addLink(_("Delete"), sprintf("?exam=%d&amp;action=delete&amp;user=%d", $data->getExamID(), $student->getStudentID()), sprintf(_("Click on this link to remove student %s from the exam."), $student->getStudentCode()));
                                }
                        }
                }

                $tree->output();
        }

        //
        // Show a tree of all examinations where caller has been assigned the
        // examinator role.
        //
        private function showAvailableExams()
        {
                printf("<h3>" . _("Examinator Tasks") . "</h3>\n");
                printf("<p>" . _("The tree of examinations shows all examination you can reschedule or add students to.") . "</p>\n");

                $tree = new TreeBuilder(_("Examinations"));
                $root = $tree->getRoot();

                $exams = Examinator::getExams(phpCAS::getUser());
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
                        )
                );

                foreach ($exams as $exam) {
                        $manager = new Manager($exam->getExamID());
                        $state = $manager->getInfo();
                        if ($state->isUpcoming()) {
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
                                        $child->setLink(sprintf("?exam=%d&action=show", $state->getInfo()->getExamID()), _("Click on this link to view and/or edit this examination"));
                                        $stobj = $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamStartTime()))));
                                        $etobj = $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamEndTime()))));
                                        if ($state->isExaminatable()) {
                                                $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add", $state->getInfo()->getExamID()), _("Click on this link to add students to this examination"));
                                                $stobj->addLink(_("Change"), sprintf("?exam=%d&amp;action=edit", $state->getInfo()->getExamID()), _("Click on this link to reschedule the examination"));
                                                $etobj->addLink(_("Change"), sprintf("?exam=%d&amp;action=edit", $state->getInfo()->getExamID()), _("Click on this link to reschedule the examination"));
                                        }
                                }
                        }
                }

                $tree->output();
        }

}

$page = new ExaminatorPage();
$page->render();
?>
