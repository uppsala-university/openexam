<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/help.php
// Author: Anders Lövgren
// Date:   2009-06-28
// 
// Show usage help.
// 

include "CAS.php";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include configuration files:
// 
include "conf/config.inc";

// 
// Include required files:
// 
include "include/cas.inc";
include "include/ui.inc";

class HelpPage extends BasePage
{

        public function __construct()
        {
                parent::__construct(_("Help and information to users"));
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

                printf("<h5>" . _("Examination") . "</h5>\n");
                printf("<p>");
                printf(_("Begin taking the exam by login on to the CAS server at Uppsala university. Once logged on, the system will present the active exam that has been assigned for you."));
                printf("</p>\n");
                printf("<p>");
                printf(_("In the left side menu is all questions, grouped by in two groups: already answered and still to be answered. You can at any time (until the end of the exam) review and modify your previous answered questions."));
                printf("</p>\n");

                printf("<h5>" . _("Anonymity") . "</h5>\n");
                printf("<p>\n");
                printf(_("Your answers are saved in a database using a code associated with your real identity (your CAS-ID). Only when your exam has been finally corrected (by the teachers), your real identity is disclosed and the result is reported to UPPDOK."));
                printf("</p>\n");
        }

}

$page = new HelpPage();
$page->render();
?>
