<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/admin/index.php
// Author: Anders Lövgren
// Date:   2010-03-05
// 
// The main admin page.
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

// 
// The index page:
// 
class MainAdminPage extends AdminPage
{
    public function __construct()
    {
	parent::__construct(_("Admin"), array());
    }

    // 
    // The main entry point. This is where all processing begins.
    // 
    public function printBody()
    {
	echo "<h3>" . _("Administration") . "</h3>\n";
	echo "<p>"  . _("The admin pages let you manage almost all aspects of the system. ") . "</p>\n";

	echo "<h5>"  . _("Common tasks:") . "</h5>\n";
	echo "<ul>\n";
	$tasks = Admin::getCommonTasks();	
	foreach($tasks as $task) {
	    printf("<li><a href=\"%s\">%s</a>\n", $task['href'], $task['desc']);
	}
	echo "</ul>\n";
    }
}

$page = new MainAdminPage();
$page->render();

?>
