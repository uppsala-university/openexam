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
    // Return true if user has been assigned the requested role. These roles are
    // bound to an exam: creator, contributor, examinator or decoder. The teacher 
    // role is global and not bound to a specific exam.
    // 
    public static function userHasRole($exam, $role, $user) 
    {
	switch($role) {
	 case "teacher":
	    $sql = sprintf("SELECT COUNT(*) FROM teachers WHERE user = '%s'", $user);
	    break;
	 case "creator":
	    $sql = sprintf("SELECT COUNT(*) FROM exams WHERE id = %d AND creator = '%s'", $exam, $user);
	    break;
	 case "contributor":
	 case "examinator":
	 case "decoder":
	    $sql = sprintf("SELECT COUNT(*) FROM ${role}s WHERE exam_id = %d AND user = '%s'", $exam, $user);
	    break;
	 default:
	    return false;
	}
	
	$db = Database::getConnection();
	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
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
    private $ldap;
    
    public function __construct($title, $params, $template = "standard")
    {
	$this->params = $params;	
	$this->roles = Teacher::getRoleCount(phpCAS::getUser());

	$this->authorize();
	$this->validate();
	
	parent::__construct($title, $template);
    }

    public function __destruct()
    {
	if(isset($this->ldap)) {
	    $this->ldap->close();
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
    // are automatic injected into $this->param.
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
		} else {
		    $this->param->$param = $_REQUEST[$param];    // Inject washed parameter
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
	}
	$this->ldap->setAttributeFilter(array("cn"));
	
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
	}
	$this->ldap->setAttributeFilter(array("cn", "mail"));
	
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
    }
}

?>
