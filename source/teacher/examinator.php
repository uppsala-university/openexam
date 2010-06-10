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
if(!file_exists("../../conf/database.conf")) {
    header("location: setup.php?reason=database");
}
if(!file_exists("../../conf/config.inc")) {
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
include "include/teacher.inc";
include "include/teacher/manager.inc";
include "include/teacher/examinator.inc";

if(!defined("EXAMINATOR_VISIBLE_IDENTITIES")) {
    define ("EXAMINATOR_VISIBLE_IDENTITIES", true);
}

// 
// The examinator page:
// 
class ExaminatorPage extends TeacherPage
{
    private $params = array( "exam"   => "/^\d+$/",
			     "action" => "/^(add|edit|show|delete)$/" );
    
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
	if(isset($_REQUEST['exam'])) {
	    self::checkAccess($_REQUEST['exam']);
	}
	
	//
	// Bussiness logic:
	//
	if(isset($_REQUEST['exam'])) {
	    if(!isset($_REQUEST['action'])) {
		$_REQUEST['action'] = "show";
	    }
	    if($_REQUEST['action'] == "add") {
		if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
		    if(isset($_REQUEST['code'])) {
			self::assert('user');
			self::saveAddStudent($_REQUEST['exam'], $_REQUEST['user'], $_REQUEST['code']);
		    } else {
			self::assert('user');
			self::saveAddStudents($_REQUEST['exam'], $_REQUEST['user']);
		    }
		} else {
		    self::formAddStudents($_REQUEST['exam']);
		}
	    } elseif($_REQUEST['action'] == "edit") {
		if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
		    self::assert(array('stime', 'etime'));
		    self::saveEditSchedule($_REQUEST['exam'], $_REQUEST['stime'], $_REQUEST['etime']);
		} else {
		    self::formEditSchedule($_REQUEST['exam']);
		}
	    } elseif($_REQUEST['action'] == "show") {
		self::showExam($_REQUEST['exam']);
	    } elseif($_REQUEST['action'] == "delete") {
		self::assert('user');
		self::deleteStudent($_REQUEST['exam'], $_REQUEST['user']);
	    }
	} else {
	    self::showAvailableExams();
	}
    }

    // 
    // Verify that the caller has been granted the required role on this exam.
    // 
    private function checkAccess($exam)
    {
	$role = "examinator";
	
	if(!Teacher::userHasRole($exam, $role, phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), $role));
	    exit(1);
	}
    }

    // 
    // Show the form for rescheduling the exam.
    // 
    private function formEditSchedule($exam)
    {
	$manager = new Manager($exam);
	$data = $manager->getData();
	
	printf("<h3>" . _("Reschedule Examination") . "</h3>\n");
	printf("<p>"  . _("This page let you reschedule the start and end time of the examination.") . "</p>\n");

	$form = new Form("examinator.php", "GET");
	$form->addHidden("exam", $exam);
	$form->addHidden("mode", "save");
	$form->addHidden("action", "edit");
	$input = $form->addTextBox("stime", strftime(DATETIME_FORMAT, strtotime($data->getExamStartTime())));
	$input->setLabel(_("Starts"));
	$input->setSize(25);
	$input = $form->addTextBox("etime", strftime(DATETIME_FORMAT, strtotime($data->getExamEndTime())));
	$input->setLabel(_("Ends"));
	$input->setSize(25);
	$form->addSpace();
	$input = $form->addSubmitButton("submit", _("Submit"));
	$input->setLabel();
	$form->output();
    }
    
    // 
    // Save rescheduled start and end time for this exam.
    // 
    private function saveEditSchedule($exam, $stime, $etime)
    {
	$handler = new Examinator($exam);
	$handler->setSchedule(strtotime($stime), strtotime($etime));
	
	header(sprintf("location: examinator.php?exam=%d", $exam));
    }
    
    // 
    // Show form for adding student(s).
    // 
    private function formAddStudents($exam)
    {
	printf("<h3>" . _("Add Students") . "</h3>\n");
	printf("<p>"  . _("You can add students one by one or as a list of codes/student ID pairs. The list should be a newline separated list of username/code pairs where the username and code is separated by tabs.") . "</p>\n");
	printf("<p>"  . _("If the student code is missing, then the system is going to generate a random code.") . "</p>\n");

	$form = new Form("examinator.php", "GET");
	$form->addSectionHeader(_("Add student one by one:"));
	$form->addHidden("exam", $exam);
	$form->addHidden("mode", "save");
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

	$form = new Form("examinator.php", "POST");
	$form->addSectionHeader(_("Add list of students:"));
	$form->addHidden("exam", $exam);
	$form->addHidden("mode", "save");
	$form->addHidden("action", "add");
	$input = $form->addTextArea("user", "user1\tcode1\nuser2\tcode2\n");
	$input->setLabel(_("Students"));
	$input->setTitle(_("Click inside the textarea to clear its content."));
	$input->setEvent(EVENT_ON_CLICK, EVENT_HANDLER_CLEAR_CONTENT);
	$input->setClass("students");
	$form->addSpace();
	$input = $form->addSubmitButton("submit", _("Submit"));
	$input->setLabel();
	$form->output();
    }
    
    // 
    // Save a single student.
    // 
    private function saveAddStudent($exam, $user, $code)
    {
	$handler = new Examinator($exam);
	$handler->addStudent($user, $code);
	header(sprintf("location: examinator.php?exam=%d", $exam));
    }

    // 
    // Save a list of students.
    // 
    private function saveAddStudents($exam, $user)
    {
	$users = explode("\n", trim($user));
	print_r($users);
	foreach($users as $row) {
	    if(($line = trim($row)) != "") {
		if(strstr($line, "\t")) {
		    list($user, $code) = explode("\t", trim($row)); 
		    $data[$user] = $code;
		} else {
		    $data[$line] = null;
		}
	    }
	}
	
	$handler = new Examinator($exam);
	$handler->addStudents($data);
	header(sprintf("location: examinator.php?exam=%d", $exam));
    }

    // 
    // Delete this student. The user argument is the numeric ID of the student.
    // 
    private function deleteStudent($exam, $user)
    {
	$handler = new Examinator($exam);
	$handler->removeStudent($user);
	header(sprintf("location: examinator.php?exam=%d", $exam));
    }
    
    // 
    // Show this single exam.
    // 
    private function showExam($exam)
    {
	$manager = new Manager($exam);
	$data = $manager->getData();
	$info = $manager->getInfo();

	$handler = new Examinator($exam);
	$students = $handler->getStudents();
	
	printf("<h3>" . _("Showing Examination") . "</h3>\n");
	printf("<p>" . 
	       _("Showing details for the examination <i>'%s'</i>. ") . 
	       _("Use the links to add and remove students, and reschedule the start and end time of the examination. ") .
	       "</p>\n", utf8_decode($data->getExamDescription()));
	
	if(!EXAMINATOR_VISIBLE_IDENTITIES) {
	    printf("<p><img src=\"../icons/nuvola/info.png\" /> "  . _("No usernames will be exposed unless the examination has already been decoded.") . "</p>\n");
	}
	
	$tree = new TreeBuilder(utf8_decode($data->getExamName()));
	$root = $tree->getRoot();
	$child = $root->addChild(_("Properties:"));
	$stobj = $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($data->getExamStartTime()))));
	$etobj = $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($data->getExamEndTime()))));
	$child->addChild(sprintf("%s: %s", _("Creator"), $this->getFormatName($data->getExamCreator())));
	$child->addChild(sprintf("%s: %d", _("Students"), $students->count()));
	
	if($info->isExaminatable()) {
	    $stobj->addLink(_("Change"), 
			    sprintf("?exam=%d&amp;action=edit", $data->getExamID()),
			    _("Click on this link to reschedule the examination"));
	    $etobj->addLink(_("Change"), 
			    sprintf("?exam=%d&amp;action=edit", $data->getExamID()),
			    _("Click on this link to reschedule the examination"));
	}

	$child = $root->addChild(_("Students"));
	if($info->isExaminatable()) {
	    $child->addLink(_("Add"), 
			    sprintf("?exam=%d&amp;action=add", $data->getExamID()),
			    _("Click on this link to add students to this examination"));
	}
	if($students->count() > 0) {
	    foreach($students as $student) {
		if($info->isDecoded() || EXAMINATOR_VISIBLE_IDENTITIES) {
		    $student->setStudentName($this->getFormatName($student->getStudentUser()));
		} else {
		    $student->setStudentName("xxx");
		}
		$subobj = $child->addChild(sprintf("<code>%s -> %s</code>", 
						   $student->getStudentCode(), 
						   $student->getStudentName()));
		if($info->isExaminatable()) {
		    $subobj->addLink(_("Delete"), 
				     sprintf("?exam=%d&amp;action=delete&amp;user=%d", 
					     $data->getExamID(), $student->getStudentID()),
				     sprintf(_("Click on this link to remove student %s from the exam."),
					     $student->getStudentCode()));
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
	printf("<p>"  . _("The tree of examinations shows all examination you can reschedule or add students to.") . "</p>\n");

	$tree = new TreeBuilder(_("Examinations"));
	$root = $tree->getRoot();
	
	$exams = Examinator::getExams(phpCAS::getUser());	
	foreach($exams as $exam) {
	    $manager = new Manager($exam->getExamID());
	    $info = $manager->getInfo();
	    
	    $child = $root->addChild(utf8_decode($exam->getExamName()));
	    $child->setLink(sprintf("?exam=%d&action=show", $exam->getExamID()), 
			    _("Click on this link to view and/or edit this examination"));
	    $stobj = $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($exam->getExamStartTime()))));
	    $etobj = $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($exam->getExamEndTime()))));
	    
	    if($info->isExaminatable()) {
		$child->addLink(_("Add"), 
				sprintf("?exam=%d&amp;action=add", $exam->getExamID()),
				_("Click on this link to add students to this examination"));
		$stobj->addLink(_("Change"), 
				sprintf("?exam=%d&amp;action=edit", $exam->getExamID()),
				_("Click on this link to reschedule the examination"));
		$etobj->addLink(_("Change"), 
				sprintf("?exam=%d&amp;action=edit", $exam->getExamID()),
				_("Click on this link to reschedule the examination"));
	    }
	    $child->addChild(sprintf("%s: %s", _("Creator"), $this->getFormatName($exam->getExamCreator())));
	}
	$tree->output();
    }
    
}

$page = new ExaminatorPage();
$page->render();

?>
