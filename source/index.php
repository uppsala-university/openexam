<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/index.php
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// The main entry point for the web application.
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
// $GLOBALS['logon'] = true;
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
include "include/html.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include bussiness logic:
//
include "include/exam.inc";

// 
// This class implements a basic page.
// 
class IndexPage extends BasePage
{

        //
        // Construct the start page.
        //
        public function __construct()
        {
                parent::__construct(_("Index:"));   // Internationalized with GNU gettext
        }

        //
        // The start page body.
        //
        public function printBody()
        {
                $content = new Content();

                $content->addHeader(_("Welcome!"));
                $content->addParagraph(
                    sprintf(_("This system let you do examination online, see <a href=\"%s\">Help</a> for more information. Remember to <u>save your answer at regular interval</u> to prevent losing data caused by automatic logout!"), "help.php")
                );

                $content->addHeader(_("Are you ready to begin the examination?"), 5);
                $content->addParagraph(
                    sprintf(_("Follow the link to the <a href=\"%s\">examination page</a> to begin the examination. You will be prompted to logon using your UU-ID (CAS logon)."), "exam/")
                );
                $content->output();
        }

        //
        // Menus for examination managers or admins.
        //
        public function printMenu()
        {
                printf("<span id=\"menuhead\">%s:</span>\n", _("Manager"));
                printf("<ul>\n");
                printf("<li><a href=\"teacher/\" class=\"menubarlink\">%s</a></li>\n", _("Teacher"));
                printf("<li><a href=\"admin/\"   class=\"menubarlink\">%s</a></li>\n", _("Admin"));
                printf("</ul>\n");
        }

}

// 
// Validate request parameters and (if validate succeeds) render the page.
// 
$page = new IndexPage();
$page->render();
?>
