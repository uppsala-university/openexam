<?php

// 
// Copyright (C) 2009 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   template/page.php
// Author: Anders Lövgren
// Date:   2009-06-25
// 
// This is template script does the following:
// 
//   1. Check that the system is configured.
//   2. Authenticate the user (optional).
//   3. Validate request parameters.
// 
// The database connection has lazy initialization. Just call 
// Database::getConnection() to start use it.
//
// 
// System check:
// 
if (!file_exists("../conf/database.conf")) {
        header("location: admin/setup.php?reason=database");
}
if (!file_exists("../conf/config.inc")) {
        header("location: admin/setup.php?reason=config");
}

// 
// If logon is true, then CAS logon is enforced for this page.
// 
$GLOBALS['logon'] = true;

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon, user interface and support for error reporting:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// This class implements a basic page.
// 
class TemplatePage extends BasePage
{

        //
        // All possible request parameters should be added here along with
        // the regex pattern to validate its value against.
        //
        private static $params = array("action" => "/^(add|edit|delete)$/");

        //
        // Construct the template page.
        //
        public function __construct()
        {
                parent::__construct(_("Template:"), self::$params);   // Internationalized with GNU gettext
        }

        //
        // The template page body.
        //
        public function printBody()
        {
                
        }

}

// 
// Render the page. The request parameters are already validated by parent class. 
// 
$page = new TemplatePage();
$page->render();
?>
