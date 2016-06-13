<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/admin/setup.php
// Author: Anders Lövgren
// Date:   2011-01-20
//
// A simple script for reporting configuration problems.
//

define('PAGE_TITLE', 'OpenExam Setup');
define('CONTACT_STRING', _('System Manager'));

//
// We got a chicken and egg problem here. The CAS module requires config, but
// a missing config is one of the issues handled by this script. We solve this
// by providing a dummy CAS-class.
//
class phpCAS
{

        public static function isAuthenticated()
        {
                return false;
        }

}

//
// Locale and internationalization support:
//
include "include/locale.inc";

//
// Include logon and user interface support:
//
include "include/ui.inc";
include "include/error.inc";

class SetupPage extends ErrorPage
{

        private $title;
        private $message;

        public function __construct()
        {
                $this->handle();
                parent::__construct($this->title, $this->message);
        }

        private function handle()
        {
                $this->title = _("Setup problem");

                if (!isset($_REQUEST['reason'])) {
                        $_REQUEST['reason'] = "unset";
                }

                switch ($_REQUEST['reason']) {
                        case "database":
                                $this->message = _("The database is not yet configured. Copy conf/database.conf.in -> conf/database.conf and edit the connection parameters.");
                                break;
                        case "config":
                                $this->message = _("The configuration file is missing. Copy conf/config.inc.in -> conf/config.inc and edit it using your favourite editor.");
                                break;
                        case "unset":
                                $this->message = _("No reason passed to this script.");
                                break;
                        default:
                                $this->message = sprintf(_("An unknown reason %s was passed to this script."), $_REQUEST['reason']);
                                break;
                }
        }

}

$setup = new SetupPage();
$setup->render();
?>
