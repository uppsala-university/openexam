<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher.inc
// Author: Anders Lövgren
// Date:   2010-04-26

// 
// Common teacher support and template page.
// 
// Teachers can create new exams and delegate the contribute, examinator 
// and decoder role to other users on those exams they owns. In one sense,
// granting teacher privileges is the bootstrap for letting users manage
// exams.
// 

class Teacher
{
    
    // 
    // Returns true of user has been granted the teacher role. The teacher role 
    // is global and not bound to a specific exam.
    // 
    public static function isTeacher($user) 
    {	
	$db = Database::getConnection();
	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
	
	$sql = sprintf("SELECT COUNT(*) FROM teachers WHERE user = '%s'", $user);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	$row = $res->fetchRow();
	
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return intval($row[0]) >= 1;	
    }
    
    // 
    // Get the number of roles assigned to this user.
    // 
    public static function getRoleCount($user)
    {
	// 
	// A left join won't work if any one of the joined tables don't contains the user:
	// 
	// $sql = sprintf("SELECT COUNT(DISTINCT d.id) AS DecoderRoles, 
	// 		       COUNT(DISTINCT e.id) AS ExaminatorRoles, 
	// 		       COUNT(DISTINCT c.id) AS ContributorRoles, 
	// 		       COUNT(DISTINCT t.id) AS ManagerRoles, 
	// 		       COUNT(DISTINCT x.id) AS CreatorRoles 
	//                 FROM decoders d LEFT JOIN examinators e ON d.user = e.user 
	// 		                LEFT JOIN contributors c ON e.user = c.user 
	// 		                LEFT JOIN teachers t ON c.user = t.user 
	// 		                LEFT JOIN exams x ON t.user = x.creator 
	// 		WHERE d.user = '%s'", $user);
	// 
	// This query is not that elegant (repeated arguments), but gets the job done quite effective:
	// 
 	$sql = sprintf("SELECT (SELECT COUNT(id) FROM decoders WHERE user = '%s') AS DecoderRoles,
		               (SELECT COUNT(id) FROM examinators WHERE user = '%s') AS ExaminatorRoles,
		               (SELECT COUNT(id) FROM contributors WHERE user = '%s') AS ContributorRoles, 
		               (SELECT COUNT(id) FROM teachers WHERE user = '%s') AS ManagerRoles, 
		               (SELECT COUNT(id) FROM exams WHERE creator = '%s') AS CreatorRoles",
		       $user, $user, $user, $user, $user);
	$res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new DataRecord($res->fetchRow());
    }
    
    //
    // Get list of all users that has been granted teacher privileges.
    //
    public static function getTeachers()
    {
	$sql = "SELECT id AS UserID, user AS UserName FROM teachers";
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }
    
    // 
    // Grant teacher rights to user.
    // 
    public static function grantUserRights($user)
    {
	$sql = sprintf("INSERT INTO teachers(user) VALUES('%s')", $user);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }

    // 
    // Revoke admin rights from users.
    // 
    public static function revokeUserRights($user_id)
    {
	$sql = sprintf("DELETE FROM teachers WHERE id = '%d'", $user_id);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
}

// 
// The base class for all teacher user interfaces, including decoder,
// contributor and examinator pages.
// 
class TeacherPage extends BasePage
{   
    private $params;
    protected $roles;
    protected $param;     // Washed request parameters
    protected $manager;
    private $ldap;
    
    public function __construct($title, $params, $template = "standard")
    {
	$this->params = $params;
	$this->roles = Teacher::getRoleCount(phpCAS::getUser());

	$this->authorize();
	$this->validate();
	
	if(isset($this->param->exam)) {
	    $this->manager = new Manager($this->param->exam);
	}
	
	parent::__construct($title, $template);
    }

    public function __destruct()
    {
	if(isset($this->ldap)) {
	    $this->ldap->close();
	}
    }

    // 
    // An helper function for printing a sub menu.
    // 
    private function printSubMenu($sect)
    {
	printf("<span id=\"menuhead\">%s:</span>\n", $sect->head);
	printf("<ul>\n");
	foreach($sect->link as $obj) {
	    if(isset($obj->target)) {
		printf("<li><a href=\"%s\" target=\"%s\">%s</a></li>\n", 
		       $obj->href, $obj->target, $obj->name);
	    } else {
		printf("<li><a href=\"%s\">%s</a></li>\n", 
		       $obj->href, $obj->name);
	    }
	}
	printf("</ul>\n");
	printf("<br/>\n");
    }
    
    // 
    // Print the left side menu.
    // 
    public function printMenu()
    {
	if(isset($this->param->exam)) {
	    // 
	    // General sub menu:
	    // 
	    if($this->manager->isCreator(phpCAS::getUser())) {
		$links->general->head = _("General");
		$links->general->link->overview->href   = sprintf("manager.php?exam=%d&action=show", $this->param->exam);
		$links->general->link->overview->name   = _("Overview");
		$links->general->link->properties->href = sprintf("manager.php?exam=%d&action=edit", $this->param->exam);
		$links->general->link->properties->name = _("Properties");
	    }
	    if($this->manager->isTestCaseAllowed(phpCAS::getUser())) {
		$links->general->head = _("General");
		if($this->manager->getInfo()->isTestCase()) {
		    $links->general->link->testcase->href = sprintf("../exam/index.php?exam=%d", $this->param->exam);
		    $links->general->link->testcase->name = _("Test");
		} elseif($this->manager->isCreator(phpCAS::getUser())) {
		    $links->general->link->testcase->href = sprintf("manager.php?exam=%d&action=test", $this->param->exam);
		    $links->general->link->testcase->name = _("Test");
		} elseif($this->manager->isContributor(phpCAS::getUser())) {
		    $links->general->link->testcase->href = sprintf("contribute.php?exam=%d&action=test", $this->param->exam);
		    $links->general->link->testcase->name = _("Test");
		}
		$links->general->link->testcase->target = "_blank";
	    }
	    
	    // 
	    // Staff sub menu:
	    // 
	    if($this->manager->isCreator(phpCAS::getUser())) {
		if($this->manager->getInfo()->isUpcoming()) {
		    $links->staff->link->contributor->href = sprintf("manager.php?exam=%d&action=add&role=contributor", $this->param->exam);
		    $links->staff->link->contributor->name = _("Add contributor");
		}
		if(!$this->manager->getInfo()->isFinished()) {
		    $links->staff->link->examinator->href  = sprintf("manager.php?exam=%d&action=add&role=examinator", $this->param->exam);
		    $links->staff->link->examinator->name  = _("Add examinator");
		}
		$links->staff->link->decoder->href = sprintf("manager.php?exam=%d&action=add&role=decoder", $this->param->exam);
		$links->staff->link->decoder->name = _("Add decoder");
		if(isset($links->staff)) {
		    $links->staff->head = _("Staff");
		}
	    }
	    
	    // 
	    // Questions sub menu:
	    // 
	    if($this->manager->isContributor(phpCAS::getUser()) || 
	       $this->manager->isCorrector(phpCAS::getUser())) {
		if($this->manager->getInfo()->isContributable()) {
		    $links->questions->link->qadd->href = sprintf("contribute.php?exam=%d&action=add", $this->param->exam);
		    $links->questions->link->qadd->name = _("Add question");
		}
		if($this->manager->isContributor(phpCAS::getUser())) {
		    $links->questions->link->qshow->href = sprintf("contribute.php?exam=%d", $this->param->exam);
		    $links->questions->link->qshow->name = _("Show all questions");
		}
		if($this->manager->getInfo()->isCorrectable()) {
		    $links->questions->link->answers->href = sprintf("correct.php?exam=%d", $this->param->exam);
		    $links->questions->link->answers->name = _("Correct answers");
		}
		if(isset($links->questions)) {
		    $links->questions->head = _("Questions");
		}
	    }

	    // 
	    // Tasks sub menu:
	    // 
	    if($this->manager->isExaminator(phpCAS::getUser()) || 
	       $this->manager->isDecoder(phpCAS::getUser())) {
		if($this->manager->isExaminator(phpCAS::getUser()) && 
		   $this->manager->getInfo()->isFinished() == false) {
		    $links->tasks->link->addstud->href  = sprintf("examinator.php?exam=%d&action=add", $this->param->exam);
		    $links->tasks->link->addstud->name  = _("Add students");
		    $links->tasks->link->schedule->href = sprintf("examinator.php?exam=%d&action=edit", $this->param->exam);
		    $links->tasks->link->schedule->name = _("Change schedule");
		}
		if($this->manager->isDecoder(phpCAS::getUser()) && 
		   $this->manager->getInfo()->isDecodable()) {
		    $links->tasks->link->decode->href = sprintf("decoder.php?exam=%d", $this->param->exam);
		    $links->tasks->link->decode->name = _("Decode answers");
		}
		if(isset($links->tasks)) {
		    $links->tasks->head = _("Tasks");
		}
	    }
	    
            printf("<span id=\"menuhead\">%s:</span>\n", _("This examination"));
	    printf("<ul>\n");
	    if(isset($links->general)) {
		self::printSubMenu($links->general);
	    }
	    if(isset($links->staff)) {
		self::printSubMenu($links->staff);
	    }
	    if(isset($links->questions)) {
		self::printSubMenu($links->questions);
	    }
	    if(isset($links->tasks)) {
		self::printSubMenu($links->tasks);
	    }
	    printf("</ul>\n");
	}
    }
    
    final public function printManagerStatus()
    {
	if(isset($this->param->exam)) {
	    $state->value = $this->manager->getInfo()->getState();
	    
	    if($state->value & EXAM_STATE_UPCOMING) {
		$state->primary = "upcoming";
	    } elseif($state->value & EXAM_STATE_RUNNING) {
		$state->primary = "running";
	    } elseif($state->value & EXAM_STATE_FINISHED) {
		$state->primary = "finished";
	    } else {
		$state->primary = "unknown";
	    }
	
	    if($state->value & EXAM_STATE_CONTRIBUTABLE) {
		$state->flags[] = "contributable";
	    }
	    if($state->value & EXAM_STATE_DECODED) {
		$state->flags[] = "decoded";
	    }
	    if($state->value & EXAM_STATE_EXAMINATABLE) {
		$state->flags[] = "examinatable";
	    }
	    if($state->value & EXAM_STATE_EDITABLE) {
		$state->flags[] = "editable";
	    }
	    if($state->value & EXAM_STATE_CORRECTABLE) {
		$state->flags[] = "correctable";
	    }
	    if($state->value & EXAM_STATE_DECODABLE) {
		$state->flags[] = "decodable";
	    }

	    $scripts = array( 
			      "manager.php" => "creator",
			      "contribute.php" => "contributor",
			      "examinator.php" => "examinator",
			      "correct.php" => "corrector",
			      "decoder.php" => "decoder"
			      );
	    
	    $roles->script = basename($_SERVER['SCRIPT_NAME']);
	    $roles->value  = array();
	    
	    foreach($scripts as $script => $role) {
		if($roles->script == $script) {
		    array_unshift($roles->value, $role);
		} else {
		    array_push($roles->value, $role);
		}
	    }
	    
	    foreach($roles->value as $role) {
		if($this->manager->hasRole(phpCAS::getUser(), $role)) {
		    if(!isset($roles->primary)) {
			$roles->primary = $role;
		    } else {
			$roles->suppl[] = $role;
		    }
		}
	    }
	    
	    $roles->title = sprintf(_("Primary role: %s\n\nSupplementary roles:\n- %s\n"), 
				      $roles->primary, 
				      implode("\n- ", $roles->suppl));
	    $state->title = sprintf(_("Additional status flags:\n- %s\n"),
				      implode("\n- ", $state->flags));
	    
	    printf("<style type=\"text/css\">\n");
	    printf("dt { font-weight: bold; float: left; }\n");
	    printf("</style>\n");
	    printf("<dl>\n");
	    printf("<dt>%s:</dt><dd>&nbsp;<a name=\"\" title=\"%s\">%s</a></dd>\n", _("Status"), $state->title, $state->primary);
	    printf("<dt>%s:</dt><dd>&nbsp;<a name=\"\" title=\"%s\">%s</a></dd>\n", _("Role"),   $roles->title, $roles->primary);
	    printf("</dl>\n");
	}
    }
    
    // 
    // Kill script execution if caller is not autorized.
    // 
    private function authorize()
    {
	if($this->roles->getDecoderRoles() == 0 &&
	   $this->roles->getExaminatorRoles() == 0 && 
	   $this->roles->getContributorRoles() == 0 && 
	   $this->roles->getManagerRoles() == 0 && 
	   $this->roles->getCreatorRoles() == 0) {
	    
	    ErrorPage::show(_("Access denied!"),
			    _("Only users granted the teacher, contributor, examinator or decoder role can access this page. The script processing has halted."));
	    exit(1);
	}
    }

    // 
    // Validate access and request parameters. All validated request parameters
    // are automatic injected into $this->param, but only if they origins from 
    // a GET or POST request.
    // 
    public function validate()
    {
	foreach($this->params as $param => $pattern) {
	    if(isset($_REQUEST[$param])) {
		if(!preg_match($pattern, $_REQUEST[$param])) {
		    ErrorPage::show(_("Request parameter error!"),
				    sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."),
					    $param, $pattern));
		    exit(1);
		} elseif(isset($_GET[$param])) {
		    $this->param->$param = $_GET[$param];     // Inject washed parameter
		} elseif(isset($_POST[$param])) {
		    $this->param->$param = $_POST[$param];    // Inject washed parameter
		}
	    }
	}
    }

    // 
    // Check that the array of required parameters is set.
    // 
    public function assert($names)
    {
	if(!is_array($names)) {
	    $names = array($names);
	}
	foreach($names as $name) {
	    if(!isset($_REQUEST[$name])) {
		ErrorPage::show(_("Missing required parameter!"),
				sprintf(_("The required request parameter '%s' is missing"), $name));
		exit(1);
	    }
	}
    }

    // 
    // Support function for doing LDAP lookup.
    // 
    protected function getCommonName($user, $lookup = LDAP_LOOKUP_BOTH)
    {
	if(!isset($this->ldap)) {
	    $this->ldap = LDAPSearch::factory();
	    $this->ldap->setAttributeFilter(array("cn", "mail"));
	}
	
 	if($lookup & LDAP_LOOKUP_UID) {	    
	    $entry = $this->ldap->searchUID($user);
	    if($entry->first()->hasCN()) {		
		return $entry->first()->getCN()->first();
	    }
	}
	if($lookup & LDAP_LOOKUP_PRINCIPAL) {
	    $entry = $this->ldap->searchPrincipalName($user);
	    if($entry->first()->hasCN()) {
		return $entry->first()->getCN()->first();
	    }
	}
	return $user;
    }

    // 
    // Similar to getCommonName(), but returns a formatted user name string.
    // 
    protected function getFormatName($user, $lookup = LDAP_LOOKUP_BOTH)
    {
	$name = $this->getCommonName($user, $lookup);
	if($name != $user) {
	    return sprintf("%s (%s)", utf8_decode($name), $user);
	} else {
	    return $user;
	}
    }

    // 
    // Get mail address object (MailRecepient).
    // 
    protected function getMailRecepient($user, $lookup = LDAP_LOOKUP_BOTH)
    {
	if(!isset($this->ldap)) {
	    $this->ldap = LDAPSearch::factory();
	    $this->ldap->setAttributeFilter(array("cn", "mail"));
	}
	
 	if($lookup & LDAP_LOOKUP_UID) {	    
	    $entry = $this->ldap->searchUID($user);
	    if($entry->first()->hasMail()) {
		return new MailRecepient(utf8_decode($entry->first()->getCN()->first()),
					 $entry->first()->getMail()->first());
	    }
	}
	if($lookup & LDAP_LOOKUP_PRINCIPAL) {
	    $entry = $this->ldap->searchPrincipalName($user);
	    if($entry->first()->hasMail()) {
		return new MailRecepient(utf8_decode($entry->first()->getCN()->first()),
					 $entry->first()->getMail()->first());
	    }
	}
	return null;
    }
}

?>
