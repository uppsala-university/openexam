<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   template/publish.inc
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// Defines published and contact info for all scripts in the same directory as
// this file is put or linked under.
//
// 
// This function returns contact/publish info to display at bottom of each page.
// 
function get_publish_info()
{
        return array(
                "mailto" => array(
                        "href" => "anders.lovgren@bmc.uu.se",
                        "name" => _("Computing Department at BMC")
                ),
                "webmaster" => array(
                        "href" => "http://www.bmc.uu.se/",
                        "name" => _("Webmaster")
                ),
                "published" => "2010-2014",
                "modified" => getlastmod()
        );
}

?>
