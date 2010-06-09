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

// 
// The index page:
// 
class ManagerPage extends TeacherPage
{
    private $params = array( "exam"   => "/^\d+$/",
			     "action" => "/^(add|edit|show|copy|delete)$/",
			     "role"   => "/^(contributor|examinator|decoder)$/",
			     "user"   => "/^\d+$/" );
    private $manager;
    
    public function __construct()
    {
	parent::__construct(_("Examination Management"), $this->params);
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
	if(isset($_REQUEST['exam'])) {
	    self::checkAccess($_REQUEST['exam']);
	} else {
	    self::checkAccess();
	}	    
	
	//
	// Bussiness logic:
	//
	if(!isset($_REQUEST['exam'])) {
	    if(!isset($_REQUEST['action'])) {
		self::showAvailableExams();
	    } elseif($_REQUEST['action'] == "add") {
		self::addExam(isset($_REQUEST['name']));
	    }
	} else {
	    $this->manager = new Manager($_REQUEST['exam']);	    
	    if(isset($_REQUEST['action'])) {
		if(isset($_REQUEST['role'])) {
		    if($_REQUEST['action'] == "delete") {
			self::assert('user');
		    }
		    if($_REQUEST['role'] == "contributor") {
			if($_REQUEST['action'] == "add") {
			    self::addContributor($_REQUEST['exam'], isset($_REQUEST['uuid']));
			} elseif($_REQUEST['action'] == "delete") {
			    self::deleteContributor($_REQUEST['exam'], $_REQUEST['user']);
			}
		    } elseif($_REQUEST['role'] == "examinator") {
			if($_REQUEST['action'] == "add") {
			    self::addExaminator($_REQUEST['exam'], isset($_REQUEST['uuid']));
			} elseif($_REQUEST['action'] == "delete") {
			    self::deleteExaminator($_REQUEST['exam'], $_REQUEST['user']);
			}
		    } elseif($_REQUEST['role'] == "decoder") {
			if($_REQUEST['action'] == "add") {
			    self::addDecoder($_REQUEST['exam'], isset($_REQUEST['uuid']));
			} elseif($_REQUEST['action'] == "delete") {
			    self::deleteDecoder($_REQUEST['exam'], $_REQUEST['user']);
			}
		    }
		} else {
		    if($_REQUEST['action'] == "show") {
			self::showExam($_REQUEST['exam']);
		    } elseif($_REQUEST['action'] == "edit") {
			self::editExam($_REQUEST['exam'], isset($_REQUEST['name']));
		    } elseif($_REQUEST['action'] == "copy") {
			self::copyExam($_REQUEST['exam']);
		    } elseif($_REQUEST['action'] == "delete") {
			self::deleteExam($_REQUEST['exam']);
		    } 
		}
	    } else {
		self::showExam($_REQUEST['exam']);
	    }
	} 
    }

    // 
    // Show all exams the current user is the owner of.
    // 
    private function showAvailableExams() 
    {
	printf("<p>"  . 
	       _("This page let you create new exams or manage your old ones. ") . 
	       _("These are the exams you are the manager of: ") .
	       "</p>\n");
	
	$tree = new TreeBuilder(_("Examinations"));
	$root = $tree->getRoot();
	if($this->roles->getManagerRoles() > 0) {
	    $root->addLink(_("Add"), "?action=add", _("Creates a new examination."));
	}
	
	$exams = Manager::getExams(phpCAS::getUser());
	foreach($exams as $exam) {
	    $state = new ExamState($exam->getExamID());
	    
	    $child = $root->addChild(utf8_decode($exam->getExamName()));
	    $child->setLink(sprintf("?exam=%d&amp;action=show", $exam->getExamID()),
			    utf8_decode($exam->getExamDescription()));
	    $child->addText(sprintf("(%s - %s)", 
				    strftime(DATETIME_FORMAT, strtotime($exam->getExamStartTime())),
				    strftime(DATETIME_FORMAT, strtotime($exam->getExamEndTime()))));
	    if($this->roles->getManagerRoles() > 0) {
		$child->addLink(_("Copy"), sprintf("?exam=%d&amp;action=copy", $exam->getExamID()), _("Create a new examination by using this examination as a template."));
		$child->addLink(_("Edit"), sprintf("?exam=%d&amp;action=edit", $exam->getExamID()), _("Edit common properties like name, description or grades for this examination."));
	    }
	    if(!$state->hasAnswers()) {
		$child->addLink(_("Delete"), sprintf("?exam=%d&amp;action=delete", $exam->getExamID()), _("Deletes the examination along with any questions."));
	    }
	}
	$tree->output();
    }
    
    // 
    // Common form for adding and editing exam properties.
    // 
    private function showExamForm($exam, $data, $action, $readonly = false)
    {
	$info = $this->manager->getInfo();
 	
	$grades = new ExamGrades($data->getExamGrades());
	
	printf("<form action=\"manager.php\" method=\"GET\">\n");
	printf("<input type=\"hidden\" name=\"action\" value=\"%s\" />\n", $action);
	if($exam != 0) {
	    printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam);
	}
	
	printf("<h5>" . _("Common Properties") . "</h5>\n");
	printf("<label for=\"unit\">%s</label>\n", _("Organization:"));
	printf("<input type=\"text\" name=\"unit\" value=\"%s\" size=\"50\" />\n", utf8_decode($data->getExamOrgUnit()));
	printf("<br />\n");
	printf("<label for=\"name\">%s</label>\n", _("Name:"));
	printf("<input type=\"text\" name=\"name\" value=\"%s\" size=\"50\" />\n", utf8_decode($data->getExamName()));
	printf("<br />\n");
	printf("<label for=\"desc\">%s</label>\n", _("Description:"));
	printf("<textarea name=\"desc\" class=\"description\">%s</textarea>\n", utf8_decode($data->getExamDescription()));
	printf("<br />\n");

	if($this->manager->getExamID() == 0 || $info->isEditable()) {
	    printf("<h5>" . _("Scheduling") . "</h5>\n");
	    printf("<label for=\"start\">%s</label>\n", _("Start time:"));
	    printf("<input type=\"text\" name=\"start\" value=\"%s\" size=\"30\" />\n", strftime(DATETIME_FORMAT, strtotime($data->getExamStartTime())));
	    printf("<br />\n");
	    printf("<label for=\"end\">%s</label>\n", _("End time:"));
	    printf("<input type=\"text\" name=\"end\" value=\"%s\" size=\"30\" />\n", strftime(DATETIME_FORMAT, strtotime($data->getExamEndTime())));
	}
	
	printf("<h5>" . _("Graduation") . "</h5>\n");
	printf("<label for=\"grade\">&nbsp;</label>\n");
	printf("<textarea class=\"grade\" name=\"grade\" title=\"%s\">%s</textarea>\n",
	       _("Input name:value pairs on separate lines defining the graduation levels on this examination. The first line must be on form name:0, denoting the failed grade."),
	       utf8_decode($grades->getText()));
	
	if(!$readonly) {
	    printf("<br /><br />\n");
	    printf("<label for=\"submit\">&nbsp;</label>\n");
	    printf("<input type=\"submit\" name=\"submit\" value=\"%s\" />\n", _("Submit"));
	}
	printf("</form>\n");
    }
    
    // 
    // Add an new exam.
    // 
    private function addExam($store)
    {	
	if(!$store) {
	    printf("<p>" . _("Define the common properties of the exam. Click on the 'Submit' button to create this exam.") . "</p>\n");
	    $data = new DataRecord( array( "examorgunit" => "Organization Unit",
					   "examname"    => "Name", 
					   "examdescription" => "Description", 
					   "examgrades"    => "{\"U\":0,\"G\":10,\"VG\":18}",
					   "examstarttime" => DATETIME_NONE,
					   "examendtime"   => DATETIME_NONE ));
	    $this->manager = new Manager(0);
	    self::showExamForm(0, $data, "add");
	} else {
	    $grades  = new ExamGrades();
	    $grades->setText($_REQUEST['grade']);
	    
	    $this->manager = new Manager(0);
	    $this->manager->setData(utf8_encode($_REQUEST['unit']),
				    utf8_encode($_REQUEST['name']),
				    utf8_encode($_REQUEST['desc']),
				    utf8_encode($grades->encode()),
				    strtotime($_REQUEST['start']),
				    strtotime($_REQUEST['end']));
	    // 
	    // By default, add creator of the exam as contributor, examinator and decoder.
	    // 
	    $this->manager->addContributor(phpCAS::getUser());
	    $this->manager->addDecoder(phpCAS::getUser());
	    header(sprintf("location: manager.php?exam=%d", $this->manager->getExamID()));
	}
    }
    
    // 
    // Edit an existing exam.
    // 
    private function editExam($exam, $store)
    {
	$data = $this->manager->getData();
	
	if(!$store) {
	    printf("<p>" . _("This page let you edit common properties of the exam. Click on the 'Submit' button to save changes.") . "</p>\n");
	    self::showExamForm($exam, $data, "edit");
	} else {
	    $grades = new ExamGrades();
	    $grades->setText($_REQUEST['grade']);
	    
	    if(!isset($_REQUEST['start'])) {
		$_REQUEST['start'] = $data->getExamStartTime();
	    }
	    if(!isset($_REQUEST['end'])) {
		$_REQUEST['end'] = $data->getExamEndTime();
	    }
	    
	    $this->manager->setData(utf8_encode($_REQUEST['unit']),
				    utf8_encode($_REQUEST['name']), 
				    utf8_encode($_REQUEST['desc']), 
				    utf8_encode($grades->encode()),
				    strtotime($_REQUEST['start']), 
				    strtotime($_REQUEST['end']));
	    header(sprintf("location: manager.php?exam=%d", $exam));
	}
    }
    
    // 
    // Creates a copy of an existing exam. The contributor, examinator and decoder is 
    // roles are preserved (but re-associated with the copy). The list of questions 
    // is also preserved, but without any associated answers.
    // 
    private function copyExam($exam)
    {
	$copy = $this->manager->copy();
	header(sprintf("location: manager.php?exam=%d&action=edit", $copy->getExamID()));
    }
    
    private function deleteExam($exam)
    {
	$this->manager->delete();
	header("location: manager.php");
    }

    // 
    // Show properties for this exam.
    // 
    private function showExam($exam) 
    {
	$data = $this->manager->getData();
	$info = $this->manager->getInfo();
	
	// 
	// Build the root node:
	// 
	$tree = new TreeBuilder(utf8_decode($data->getExamName()));
	$root = $tree->getRoot();
	if($this->roles->getManagerRoles() > 0) {
	    $root->addLink(_("Copy"), sprintf("?exam=%d&amp;action=copy", $data->getExamID()), _("Create a new examination by using this examination as a template."));
	    $root->addLink(_("Edit"), sprintf("?exam=%d&amp;action=edit", $data->getExamID()), _("Edit common properties like name, description or grades for this examination."));   // Should be limited
	}

	// 
	// Build the contributors node:
	// 
	$child = $root->addChild(_("Contributors"));
	if($info->isContributable()) {
	    $child->addLink(_("Add"), 
			    sprintf("?exam=%d&amp;action=add&amp;role=contributor", $data->getExamID()), 
			    _("Add a question contributor to this examination."));
	}
	$contributors = $this->manager->getContributors();
	foreach($contributors as $contributor) {
	    $subobj = $child->addChild($this->getFormatName($contributor->getContributorUser()));
	    if($info->isContributable()) {
		$subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=contributor&amp;user=%d", 
						      $contributor->getExamID(),
						      $contributor->getContributorID()),
				 sprintf(_("Remove %s as a question contributor for this examination."),
					 utf8_decode($this->getCommonName($contributor->getContributorUser()))));
	    }
	}
	
	// 
	// Build the examinators node:
	// 
	$child = $root->addChild(_("Examinators"));
	if($info->isExaminatable()) {
	    $child->addLink(_("Add"), 
			    sprintf("?exam=%d&amp;action=add&amp;role=examinator", $data->getExamID()),
			    _("Add a person with the examinator role."));
	}
	$examinators = $this->manager->getExaminators();
	foreach($examinators as $examinator) {
	    $subobj = $child->addChild($this->getFormatName($examinator->getExaminatorUser()));
	    if($info->isExaminatable()) {
		$subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=examinator&amp;user=%d", 
						      $examinator->getExamID(),
						      $examinator->getExaminatorID()),
				 sprintf(_("Remove %s as an examinator for this examination."),
					 utf8_decode($this->getCommonName($contributor->getContributorUser()))));
	    }
	}
	
	// 
	// Build the decoders node:
	// 
	$child = $root->addChild(_("Decoders"));
	$child->addLink(_("Add"), 
			sprintf("?exam=%d&amp;action=add&amp;role=decoder", $data->getExamID()),
			    _("Add a person with the examinator role."));
	$decoders = $this->manager->getDecoders();
	foreach($decoders as $decoder) {
	    $subobj = $child->addChild($this->getFormatName($decoder->getDecoderUser()));
	    $subobj->addLink(_("Remove"), sprintf("?exam=%d&amp;action=delete&amp;role=decoder&amp;user=%d",
						  $decoder->getExamID(),
						  $decoder->getDecoderID()),
			     sprintf(_("Remove %s as a decoder for this examination."),
					 utf8_decode($this->getCommonName($contributor->getContributorUser()))));
	}

	// 
	// Build the questions node:
	// 
	$quest = $root->addChild(_("Questions"));
	if($info->isContributable()) {
	    $quest->addLink(_("Add"), 
			    sprintf("contribute.php?exam=%d&amp;action=add", $data->getExamID()),
			    _("Add a new question for this examination."));
	    $quest->addLink(_("Remove all"), 
			    sprintf("contribute.php?exam=%d&amp;action=delete&amp;question=all", $data->getExamID()),
			    _("Remove all questions from this examination."));
	}
	if($this->manager->isContributor(phpCAS::getUser())) {
	    $quest->addLink(_("Show"), 
			    sprintf("contribute.php?exam=%d", $data->getExamID()),
			    _("Open the page showing all questions at once, where questions can be edited and previewed."));
	}
	
	$child = $quest->addChild(_("Active"));
	$questions = $this->manager->getQuestions('active');
	foreach($questions as $question) {
	    $subobj = $child->addChild(sprintf("%s %s...", 
					       utf8_decode($question->getQuestionName()),
					       utf8_decode(substr(strip_tags($question->getQuestionText()), 0, 55))));
	    if($info->isContributable()) {
		$subobj->addLink(_("Edit"), sprintf("contribute.php?exam=%d&amp;action=edit&amp;question=%d",
						    $question->getExamID(),
						    $question->getQuestionID()),
				 _("Edit properties for this question"));
		$subobj->addLink(_("Delete"), sprintf("contribute.php?exam=%d&amp;action=delete&amp;question=%d",
						      $question->getExamID(),
						      $question->getQuestionID()),
				 _("Permanent delete this question"));
	    }
	    $subobj->addLink(_("Remove"), sprintf("contribute.php?exam=%d&amp;action=remove&amp;question=%d",
						  $question->getExamID(),
						  $question->getQuestionID()),
			     _("Flag this question as removed (not deleted permanent). Can later be restored from the removed list below."));
	}
	$child = $quest->addChild(_("Removed"));
	$questions = $this->manager->getQuestions('removed');
	foreach($questions as $question) {
	    $subobj = $child->addChild(sprintf("%s %s...", 
					       utf8_decode($question->getQuestionName()),
					       utf8_decode(substr($question->getQuestionText(), 0, 60))));
	    $subobj->addLink(_("Restore"), sprintf("contribute.php?exam=%d&amp;action=restore&amp;question=%d",
						   $question->getExamID(),
						   $question->getQuestionID()),
			     _("Flag this question as active again"));
	}
	
	printf("<p>" . 
	       _("This page let you add/delete contributors, examinators, decoders and questions from this exam. ") . 
	       _("Not all options might be available, i.e. its not possible to add questions to an already started examination." ) .
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
    private function addExamRole($exam, $role, $text)
    {
	printf("<p>%s</p>\n", $text);
	printf("<form action=\"manager.php\" method=\"GET\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam);
	printf("<input type=\"hidden\" name=\"action\" value=\"add\" />\n");
	printf("<input type=\"hidden\" name=\"role\" value=\"%s\" />\n", $role);
	printf("<label for=\"uuid\">%s</label>\n",  _("UU-ID:"));
	printf("<input type=\"text\" name=\"uuid\" />\n");
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Add"));
	printf("</form>\n");
    }

    private function addContributor($exam, $store)
    {
	if(!$store) {
	    $data = $this->manager->getData();
	    $text = sprintf(_("Allow this user to contribute questions for the examination '%s' by granting he/she the 'contribute' role."),
			    utf8_decode($data->getExamName()));
	    return self::addExamRole($exam, "contributor", $text);
	}
	
	$this->manager->addContributor($_REQUEST['uuid']);
	header(sprintf("location: manager.php?exam=%d&action=show", $exam));
    }

    private function deleteContributor($exam, $user)
    {
	$this->manager->deleteContributor($user);
	header(sprintf("location: manager.php?exam=%d", $exam));
    }

    private function addExaminator($exam, $store)
    {
	if(!$store) {
	    $data = $this->manager->getData();
	    $text = sprintf(_("Allow this user to add students for the examination '%s' by granting he/she the 'examinator' role."),
			    utf8_decode($data->getExamName()));
	    return self::addExamRole($exam, "examinator", $text);
	}
	
	$this->manager->addExaminator($_REQUEST['uuid']);
	header(sprintf("location: manager.php?exam=%d&action=show", $exam));
    }

    private function deleteExaminator($exam, $user)
    {
	$this->manager->deleteExaminator($user);
	header(sprintf("location: manager.php?exam=%d", $exam));
    }

    private function addDecoder($exam, $store)
    {
	if(!$store) {
	    $data = $this->manager->getData();
	    $text = sprintf(_("Allow this user to decode the real identity behind the students assigned for the examination '%s' by granting he/she the 'decoder' role."),
			    utf8_decode($data->getExamName()));
	    return self::addExamRole($exam, "decoder", $text);
	}
	
	$this->manager->addDecoder($_REQUEST['uuid']);
	header(sprintf("location: manager.php?exam=%d&action=show", $exam));
    }
    
    private function deleteDecoder($exam, $user)
    {
	$this->manager->deleteDecoder($user);
	header(sprintf("location: manager.php?exam=%d", $exam));
    }
    
    // 
    // Verify that the caller has been granted the required role.
    // 
    private function checkAccess($exam = 0)
    {
	if($exam != 0) {
	    $role = "creator";
	    if(!Teacher::userHasRole($exam, $role, phpCAS::getUser())) {
		ErrorPage::show(_("Access denied!"),
				sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), $role));
		exit(1);
	    }
	} else {
	    if($this->roles->getCreatorRoles() == 0 && $this->roles->getManagerRoles() == 0) {
		ErrorPage::show(_("Access denied!"),
				_("Only users granted the teacher role or being the creator on at least one exam can access this page. The script processing has halted."));
		exit(1);
	    }
	}
    }
}

$page = new ManagerPage();
$page->render();

?>
