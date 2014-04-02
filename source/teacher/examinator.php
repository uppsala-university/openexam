<?php

// 
// Copyright (C) 2010-2014 Computing Department BMC, 
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
include "include/import.inc";

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

        private static $params = array(
                "exam"    => parent::pattern_index,
                "what"    => "/^(user|users|course|file)$/",
                "mode"    => "/^(save|show)$/",
                "code"    => parent::pattern_code,
                "user"    => parent::pattern_user,
                "users"   => parent::pattern_textarea,
                "course"  => parent::pattern_course,
                "stime"   => parent::pattern_textline,
                "etime"   => parent::pattern_textline,
                "action"  => "/^(add|edit|show|delete)$/",
                "year"    => parent::pattern_year,
                "order"   => "/^(state|name|date)$/",
                "termin"  => parent::pattern_termin,
                "file"    => parent::pattern_textline,
                "account" => parent::pattern_index,
                "persnr"  => parent::pattern_index,
                "type"    => parent::pattern_index,
                "show"    => "/^(course|file)$/"
        );

        public function __construct()
        {
                self::$params['tag'] = parent::pattern(array(parent::pattern_empty, parent::pattern_index, parent::pattern_name));
                self::$params['type'] = parent::pattern(array(parent::pattern_empty, parent::pattern_index, parent::pattern_name));
                parent::__construct(_("Examinator Page"), self::$params);
                if (!isset($this->param->order)) {
                        $this->param->order = "state";
                }
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
                        if (!isset($this->param->action)) {
                                $this->param->action = "show";
                        }
                        if ($this->param->action == "add") {
                                if (isset($this->param->mode) && $this->param->mode == "save") {
                                        if (isset($this->param->what)) {
                                                if ($this->param->what == "user") {
                                                        $this->assert(array(
                                                                'user',
                                                                'code'));
                                                        $this->saveAddStudent();
                                                } elseif ($this->param->what == "users") {
                                                        $this->assert('users');
                                                        $this->saveAddStudents();
                                                } elseif ($this->param->what == "course") {
                                                        $this->assert(array(
                                                                'course',
                                                                'year',
                                                                'termin'));
                                                        $this->saveAddCourse();
                                                } elseif ($this->param->what == "file") {
                                                        $this->saveAddFile();
                                                } else {
                                                        $this->formAddStudents($this->param->what);
                                                }
                                        }
                                } elseif (isset($this->param->mode) && $this->param->mode == "show") {
                                        if (isset($this->param->show)) {
                                                if ($this->param->show == "course") {
                                                        $this->assert(array(
                                                                'course', 'year', 'termin'
                                                            )
                                                        );
                                                        $this->showAddCourse();
                                                } elseif ($this->param->show == "file") {
                                                        $this->assert(array(
                                                                'file', 'tag', 'account', 'persnr'
                                                            )
                                                        );
                                                        $this->showAddFile();
                                                }
                                        }
                                } else {
                                        if (!isset($this->param->what)) {
                                                $this->param->what = "course";
                                        }
                                        $this->formAddStudents($this->param->what);
                                }
                        } elseif ($this->param->action == "edit") {
                                if (isset($this->param->mode) && $this->param->mode == "save") {
                                        $this->assert(array(
                                                'stime',
                                                'etime'));
                                        $this->saveEditSchedule();
                                } else {
                                        $this->formEditSchedule();
                                }
                        } elseif ($this->param->action == "show") {
                                $this->showExam();
                        } elseif ($this->param->action == "delete") {
                                $this->assert('user');
                                $this->deleteStudent();
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
                        $this->fatal(_("Access denied!"), sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), "examinator"));
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

        private function showAddCommon($what)
        {
                printf("<h3>" . _("Add Students") . "</h3>\n");
                printf("<p>" . _("You can add students one by one or as a list of codes/student ID pairs. The list should be a newline separated list of username/code pairs where the username and code is separated by tabs.") . "</p>\n");
                printf("<p>" . _("If the student code is missing, then the system is going to generate a random code.") . "</p>\n");

                $mode = array(
                        "user"   => _("Single"),
                        "users"  => _("List"),
                        "file"   => _("File"),
                        "course" => _("Import")
                );
                $disp = array(
                );
                printf("<span class=\"links viewmode\">\n");
                foreach ($mode as $name => $text) {
                        if ($what != $name) {
                                $disp[] = sprintf("<a href=\"?exam=%d&amp;action=add&amp;what=%s\">%s</a>", $this->param->exam, $name, $text);
                        } else {
                                $disp[] = $text;
                        }
                }
                printf("%s: %s\n", _("Add"), implode(", ", $disp));
                printf("</span>\n");
        }

        //
        // Show form for adding student(s).
        //
        private function formAddStudents($what)
        {
                $this->showAddCommon($what);

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
                        $form->addHidden("mode", "show");
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
                        $input = $form->addSubmitButton("submit", _("Show"));
                        $input->setLabel();
                        $form->output();
                } elseif ($what == "file") {
                        $form = new Form("examinator.php", "POST");
                        $form->setEncodingType("multipart/form-data");
                        $form->addSectionHeader(_("Import file"));
                        $form->addHidden("exam", $this->param->exam);
                        $form->addHidden("mode", "save");
                        $form->addHidden("what", "file");
                        $form->addHidden("action", "add");
                        $input = $form->addFileInput("file");
                        $input->setLabel(_("Filename"));
                        $input->setAccept("application/vnd.ms-excel,text/tab-separated-values,text/csv");
                        $input->setTitle(_("Select an Excel or plain text (TAB- or CSV-separated) file."));
                        $form->addSpace();
                        $input = $form->addComboBox('type');
                        $input->addOption(-1, _("Autodetect"));
                        $types = array(
                                "excel5"    => "Microsoft Excel 4.x - 5.0/95 (*.xls)",
                                "excel97"   => "Microsoft Excel 97/2000/XP/2003 (*.xls)",
                                "excel2003" => "Microsoft Excel 2003 XML (*.xls)",
                                "excel2007" => "Microsoft Excel 2007/2010 XML (*.xlsx)",
                                "oocalc"    => "Open Document Format Spreadsheet (*.ods)",
                                "gnumeric"  => "Gnome Gnumeric Spreadsheet (*.gnumeric)",
                                "tab"       => "Tab Separated Values (*.tab|*.tsv|*.txt)",
                                "csv"       => "Comma Separated Values (*.csv)"
                        );
                        foreach ($types as $type => $name) {
                                $input->addOption($type, $name);
                        }
                        $input->setLabel(_("File type"));

                        $form->addSpace();
                        $input = $form->addTextBox("tag");
                        $input->setLabel(_("Tag"));
                        $input->setTitle(_("Tag each student in the uploaded list with this identifier. If numeric, then it denotes the column (starting from index 0) in the submitted list containing the tag."));

                        $input = $form->addComboBox("account");
                        $input->addOption(-1, _("Unused"));
                        for ($i = 0; $i < 10; $i++) {
                                $input->addOption($i, $i);
                        }
                        $input->setLabel(_("Account"));
                        $input->setTitle(_("Index of the column containing the account names."));

                        $input = $form->addComboBox("code");
                        $input->addOption(-1, _("Unused"));
                        for ($i = 0; $i < 10; $i++) {
                                $input->addOption($i, $i);
                        }
                        $input->setLabel(_("Code"));
                        $input->setTitle(_("Index of the column containing anonymous code."));

                        $input = $form->addComboBox("persnr");
                        $input->addOption(-1, _("Autodetect"));
                        for ($i = 0; $i < 10; $i++) {
                                $input->addOption($i, $i);
                        }
                        $input->setLabel(_("Pers.Nr"));
                        $input->setTitle(_("Index of the column containing the personal numbers (i.e. 751011-3723)."));
                        $form->addSpace();

                        $input = $form->addSubmitButton("submit", _("Submit"));
                        $input->setLabel();
                        $form->output();

                        $msgbox = new MessageBox(MessageBox::information, _("This form accepts table data files containing student registrations. ") .
                            _("Accepted file types are like Excel and plain text formats (TAB- or CSV-separated). ") .
                            _("Notice that all column numbers are indexed from 0. ")
                        );
                        $msgbox->display();
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
        // Show list of students that is going to be imported from file.
        // 
        private function showAddFile()
        {
                $this->showAddCommon("file");
        }

        //
        // Show list of students that is going to be imported from UPPDOK.
        //
        private function showAddCourse()
        {
                $this->showAddCommon("course");

                $header = new H4(_("Import from UPPDOK"));
                $header->setClass("secthead");
                $header->output();

                $uppdok = new UppdokData($this->param->year, $this->param->termin);
                $uppdok->setCompactMode(false);
                $users = $uppdok->members($this->param->course);

                if (count($users) > 0) {
                        $paragraph = new Paragraph();
                        $paragraph->addText(sprintf(_("Click the 'Add Students' button to add these students registered on the course %s."), $this->param->course));
                        $paragraph->output();

                        $table = new Table();
                        $row = $table->addRow();
                        $row->addHeader(_('Login'));
                        $row->addHeader(_('Name'));
                        foreach ($users as $user) {
                                if ($user->getUser() != null) {
                                        $row = $table->addRow();
                                        $row->addData($user->getUser());
                                        $row->addData($user->getName());
                                }
                        }
                        $table->output();

                        $form = new Form("examinator.php", "POST");
                        $form->addHidden("exam", $this->param->exam);
                        $form->addHidden("mode", "save");
                        $form->addHidden("what", "course");
                        $form->addHidden("year", $this->param->year);
                        $form->addHidden("termin", $this->param->termin);
                        $form->addHidden("course", $this->param->course);
                        $form->addHidden("action", "add");
                        $form->addSpace();
                        $input = $form->addSubmitButton("submit", _("Add Students"));
                        $form->output();
                } else {
                        $this->fatal(_("No members found"), sprintf(_("The query for course %s in the directory service returned an empty list. ") .
                                _("It looks like no students belongs to this course."), $this->param->course));
                }
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
                        $this->fatal(_("No members found"), sprintf(_("The query for course %s in the directory service returned an empty list. ") .
                                _("It looks like no students belongs to this course."), $this->param->course));
                }

                header(sprintf("location: examinator.php?exam=%d", $this->param->exam));
        }

        // 
        // Handle file upload with students to import.
        // 
        private function saveAddFile()
        {
                try {
                        $this->param->form = "sr";      // constant

                        $inserter = new ImportInsert($this->param->exam, Database::getConnection());

                        $importer = FileImport::create($this->param->form, $this->param->type);
                        $importer->setFile($_FILES['file']['name'], $_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['size']);
                        $importer->setFilter(OPENEXAM_IMPORT_INCLUDE_STUDENTS);

                        if (isset($this->param->tag) && strlen($this->param->tag) != 0) {
                                $importer->setTagging($this->param->tag);
                        }
                        if ($this->param->account != -1) {
                                $importer->setMapping(ImportStudents::user, $this->param->account);
                        }
                        if ($this->param->persnr != -1) {
                                $importer->setMapping(ImportStudents::pnr, $this->param->persnr);
                        }
                        if ($this->param->code != -1) {
                                $importer->setMapping(ImportStudents::code, $this->param->code);
                        }

                        $importer->open();
                        $importer->read();
                        $importer->close();

                        $importer->insert($inserter);
                } catch (ImportException $exception) {
                        $this->fatal(_("Failed Import Questions"), $exception->getMessage());
                }
                header(sprintf("location: contribute.php?exam=%d", $this->param->exam));
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
                        MessageBox::show(MessageBox::information, _("No usernames will be exposed unless the examination has already been decoded."));
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
                $utils = new TeacherUtils($this, phpCAS::getUser());
                $utils->listAssistable($this->param->order);
        }

}

$page = new ExaminatorPage();
$page->render();

?>
