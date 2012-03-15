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
include "include/html.inc";

class HelpPage extends BasePage
{

        public function __construct()
        {
                parent::__construct(_("Help and information to users"));
        }

        public function printBody()
        {
                $content = new Content();
                $content->addHeader(_("Help and information"));

                $content->addHeader(_("Logon"), 5);
                $content->addParagraph(_("You have to be logged on using your UU identity (CAS login) for doing administative tasks."));
                $content->addParagraph(
                    array(
                            _("You logon to the <a href=\"https://cas.user.uu.se\">CAS-server at Uppsala University</a> using your UU identity and password A."),
                            _("You will be automatically prompted to logon whenever it's required to continue.")
                ));

                $content->addHeader(_("Examination"), 5);
                $content->addParagraph(_("Begin taking the exam by login on to the CAS server at Uppsala university. Once logged on, the system will present the active exam that has been assigned for you."));
                $content->addParagraph(_("In the left side menu is all questions, grouped by in two groups: already answered and still to be answered. You can at any time (until the end of the exam) review and modify your previous answered questions."));

                $content->addHeader(_("Anonymity"), 5);
                $content->addParagraph(_("Your answers are saved in a database using a code associated with your real identity (your CAS-ID). Only when your exam has been finally corrected (by the teachers), your real identity is disclosed and the result is reported to UPPDOK."));

                $content->output();
        }

}

$page = new HelpPage();
$page->render();
?>
