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

class AboutPage extends BasePage
{
    public function __construct()
    {
	parent::__construct(_("About this system"));
    }
    
    public function printBody()
    {
	printf("<h3>" . _("Information about this system") . "</h3>\n");
    	
	printf("<h5>" . _("End user requirements") . "</h5>\n");
	printf("<p>");
	printf(_("You have to use a web browser with cookies activated to use this system. "));
	printf(_("The web browser must have support for javascript enabled."));
	printf("</p>\n");
	
	printf("<h5>" . _("General") . "</h5>\n");
	printf("<p>"  . 
	       _("This system was developed by <a href=\"mailto:%s\">%s</a> (%s). ") . 
	       _("Please visit the <a href=\"%s\">project page</a> for further information.") . 
	       "</p>\n",
	       "anders.lovgren@bmc.uu.se",
	       "Anders Lövgren",
	       _("Computing Department at BMC"),
	       "http://it.bmc.uu.se/andlov/proj/openexam/");
    }
}

$page = new AboutPage();
$page->render();

?>
