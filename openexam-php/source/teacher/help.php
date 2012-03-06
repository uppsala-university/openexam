<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/help.php
// Author: Anders Lövgren
// Date:   2009-06-28
// 
// Show usage help.
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
// Include configuration files:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include required files:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Required for top menu support:
// 
include "include/teacher.inc";

class HelpPage extends BasePage
{

        public function __construct()
        {
                parent::__construct(_("Help and information to teachers"));
        }

        public function printBody()
        {
                printf("<h3>" . _("Help and information") . "</h3>\n");

                printf("<h5>" . _("Logon") . "</h5>\n");
                printf("<p>");
                printf(_("You have to be logged on using your UU identity (CAS login) for doing administative tasks. "));
                printf("</p>\n");
                printf("<p>");
                printf(_("You logon to the <a href=\"https://cas.user.uu.se\">CAS-server at Uppsala University</a> using your UU identity and password A. "));
                printf(_("You will be automatically prompted to logon whenever it's required to continue. "));
                printf("</p>\n");

                printf("<h5>" . _("Manual") . "</h5>\n");
                printf("<p>\n");
                printf(_("The <a href=\"%s\">system user manual</a> is available online with tips and examples."), "http://it.bmc.uu.se/andlov/proj/openexam/manual/");
                printf("</p>\n");

                printf("<h5>" . _("Getting started") . "</h5>\n");
                printf("<p>" . _("If you like to use this system for your own online examinations, please contact %s to get teacher access and further instructions.") . "</p>\n", CONTACT_STRING);
                printf("<p>" . _("The <a href=\"%s\" target=\"_blank\">keynotes page</a> contains a short introduction to the most fundamental parts of the system.") . "</p>\n", "keynotes.php");
        }

}

$page = new HelpPage();
$page->render();
?>
