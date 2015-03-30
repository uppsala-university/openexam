<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/error.inc
// Author: Anders Lövgren
// Date:   2009-08-07
//
// 
// Generic error reporting class that shows an error page.
// 
class ErrorPage extends BasePage
{

        private $title;
        private $message;

        public function __construct($title, $message)
        {
                //
                // Don't clear output buffer unless its active:
                //
                if (ob_get_contents()) {
                        ob_end_clean();
                }

                parent::__construct($title);

                $this->title = $title;
                $this->message = $message;
        }

        public function printBody()
        {
                printf("<h3>%s</h3>\n", $this->title);
                printf("<p>%s</p><p>" . _("If you think this is a bug in the system, then please report it to %s") . "</p>\n", $this->message, CONTACT_STRING);
        }

        public static function show($title, $message)
        {
                $page = new ErrorPage($title, $message);
                $page->render();
        }

}

?>
