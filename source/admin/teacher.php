<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/admin/admin.php
// Author: Anders Lövgren
// Date:   2010-04-26
// 
// The teacher admin page. This page lets authorized user (supervisors) grant 
// or revoke the teacher role for other users.
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

// 
// Business logic:
// 
include "include/admin.inc";
include "include/teacher.inc";
include "include/ldap.inc";

// 
// The index page:
// 
class TeacherAdminPage extends AdminPage
{
    private $params = array( 
			     "action" => "/^(grant|revoke)$/",
			     "user"   => "/^[0-9a-z]{1,8}$/"
			   );
    
    public function __construct()
    {
	parent::__construct(_("Teacher Admin"), $this->params);
    }

    // 
    // The main entry point. This is where all processing begins.
    // 
    public function printBody()
    {	
	if(isset($_REQUEST['action'])) {
	    // 
	    // Check required request parameters:
	    // 
	    if(!isset($_REQUEST['user'])) {
		ErrorPage::show(_("Request parameter error!"),
				_("Missing request parameter 'user'."));
		exit(1);
	    }
	    // 
	    // Grant or revoke admin privileges:
	    // 
	    if($_REQUEST['action'] == "grant") {
		self::grantUserRights($_REQUEST['user']);
	    } elseif($_REQUEST['action'] == "revoke") {
		self::revokeUserRights($_REQUEST['user']);
	    }
	} else {
	    self::listTeacherUsers();
	}
    }
    
    
    // 
    // Grant administrative privileges to user.
    // 
    private function grantUserRights($user)
    {
	Teacher::grantUserRights($user);
	header(sprintf("Location: %s/admin/teacher.php", BASE_URL));
    }

    // 
    // Revoke administrative privileges from user.
    // 
    private function revokeUserRights($user)
    {
	Teacher::revokeUserRights($user);
	header(sprintf("Location: %s/admin/teacher.php", BASE_URL));
    }
    
    // 
    // List all users with the teacher role.
    // 
    private function listTeacherUsers()
    {
	global $locale;
	
	echo "<h3>" . _("Administration") . "</h3>\n";
	echo "<p>" . 
	  _("This page let you grant and revoke the teacher role for other users. ") .
	  _("These users have been granted the teacher role:") . 
	  "</p>\n";
	$ldap = LDAPSearch::factory();
	$users = Teacher::getTeachers();
	echo "<table>\n";
	foreach($users as $user) {
	    $data = $ldap->searchPrincipalName($user->getUserName());
	    printf("<tr><td>%s</td><td>%s</td><td><a href=\"?user=%s&amp;action=revoke\">" . _("Revoke") . "</a></td></tr>\n",
		   iconv("UTF8", $locale->getCharSet(), $data->first()->getCN()->first()),
		   $user->getUserName(), 
		   $user->getUserID());
	}
	echo "</table>\n";
	
	echo "<h5>" . _("Add new teacher:") . "</h5>\n";
	printf("<p>"  . _("Fill in the user name and click on '%s' to grant this user the teacher role:") . "</p>\n", _("Grant"));
	echo "<form action=\"teacher.php\" method=\"get\">\n";
	echo "  <input type=\"hidden\" name=\"action\" value=\"grant\" />\n";
	echo "  <label for=\"user\">" . _("Username:") . "</label>\n";
	echo "  <input type=\"text\" name=\"user\" />\n";
	echo "  <input type=\"submit\" value=\"" . _("Grant") . "\" />\n";
	echo "</form>\n";
    }
    
}

$page = new TeacherAdminPage();
$page->render();

?>
