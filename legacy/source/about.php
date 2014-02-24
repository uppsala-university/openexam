<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/about.php
// Author: Anders Lövgren
// Date:   2009-06-28
// 
// Show about info for this system.
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

class AboutPage extends BasePage
{

        public function __construct()
        {
                parent::__construct(_("About this system"));
        }

        public function printBody()
        {
                $content = new Content();
                $content->addHeader(_("Information about this system"));

                $content->addHeader(_("End user requirements"), 5);
                $content->addParagraph(
                    array(
                            _("You have to use a web browser with cookies activated to use this system."),
                            _("The web browser must have support for javascript enabled.")
                    )
                );

                $content->addHeader(_("General"), 5);
                $content->addParagraph(
                    array(
                            sprintf(_("This system was developed by <a href=\"mailto:%s\">%s</a> (%s)."), "anders.lovgren@bmc.uu.se", "Anders Lövgren", _("Computing Department at BMC")),
                            sprintf(_("Please visit the <a href=\"%s\">project page</a> for further information."), "http://it.bmc.uu.se/andlov/proj/openexam/")
                    )
                );
                $content->output();
        }

}

$page = new AboutPage();
$page->render();
?>
