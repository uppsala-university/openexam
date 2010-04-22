<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/admin.inc
// Author: Anders Lövgren
// Date:   2010-03-05

// 
// Common admin support and template page.
// 

class Admin
{
    // 
    // Return true if user has administrative privileges.
    // 
    public static function userIsAdmin($user) 
    {
	$db = Database::getConnection();
	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
	$sql = sprintf("SELECT COUNT(*) FROM admins
			WHERE user = '%s'", $user);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	$row = $res->fetchRow();
	
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return intval($row[0]) == 1;	
    }
    
    // 
    // Get the list of all common tasks.
    // 
    public static function getCommonTasks()
    {
	$tasks = array( "admin"    => array( "href" => "admin.php",
					     "desc" => _("Grant and revoke administrative privileges to trusted users."),
					     "name" => _("Admins")
					     )
			);
	return $tasks;
    }

    //
    // Get list of all users that has been granted administrative privileges.
    //
    public static function getAdminUsers()
    {
	$sql = "SELECT user AS UserName FROM admins";
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }
    
    // 
    // Grant admin rights to user.
    // 
    public static function grantUserRights($user)
    {
	$sql = sprintf("INSERT INTO admins(user) VALUES('%s')", $user);
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
	$sql = sprintf("DELETE FROM admins WHERE id = '%d'", $user_id);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
}

// 
// The base class for all admin user interfaces.
// 
class AdminPage extends BasePage
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
	if(!Admin::userIsAdmin(phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    _("Only users granted administrative privileges (system managers) can access this page. The script processing has halted."));
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
