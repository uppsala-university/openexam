<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/cas.inc
// Author: Anders Lövgren
// Date:   2009-06-28
// 
// Initialize the CAS connection and, by request, log out user. The file
// conf/config.inc should be included prior to this script. This script
// should be included before any output is sent to the client.
// 

// 
// Output CAS debug or not? The debug is usually output to /tmp
// 
if(defined("CAS_DEBUG") && CAS_DEBUG) {
    phpCAS::setDebug();
}

// 
// Initialize phpCAS:
// 
phpCAS::client(CAS_VERSION_2_0, CAS_HOST, CAS_PORT, CAS_PATH);

// 
// No SSL validation for the CAS server:
// 
phpCAS::setNoCasServerValidation();

// 
// Force CAS authentication for explicit logon request or if the page
// has autohentication restrictions:
// 
if(isset($_REQUEST['logon']) || isset($GLOBALS['logon'])) {
    phpCAS::forceAuthentication();
}

// 
// Logout if desired:
// 
if(isset($_REQUEST['logout'])) {
    phpCAS::logout();
}

?>
