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
    // Return true if user has been assigned one of the roles.
    // 
    public static function userIsAuthorized($user)
    {
    	$db = Database::getConnection();
    	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
	
	$found = false;
	foreach(array( "teachers", "contributors", "examinators", "decoders" ) as $role) {	    
	    $sql = sprintf("SELECT COUNT(*) FROM %s WHERE user = '%s'", $role, $user);
	    $res = $db->query($sql);
	    if(PEAR::isError($res)) {
		throw new DatabaseException($res->getMessage());
	    }
	    $row = $res->fetchRow();
	    if(intval($row[0]) >= 1) {
		$found = true;
		break;
	    }
	}
	    
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $found;
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
    
    public function __construct($title, $params)
    {
	$this->params = $params;
	$this->validate();
	
	parent::__construct($title);
    }

    // 
    // Validate access and request parameters:
    // 
    public function validate()
    {
	if(!Teacher::userIsAuthorized(phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    _("Only users granted the teacher, contributor, examinator or decoder role can access this page. The script processing has halted."));
	    exit(1);
	}
	
	foreach($this->params as $param => $pattern) {
	    if(isset($_REQUEST[$param])) {
		if(!preg_match($pattern, $_REQUEST[$param])) {
		    ErrorPage::show(_("Request parameter error!"),
				    sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."),
					    $param, $pattern));
		    exit(1);
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
}

?>
