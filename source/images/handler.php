<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/images/handler.php
// Author: Anders Lövgren
// Date:   2011-01-28
//
// This script is intended to be called as the handler for <%type str %> format
// strings embedded in questions.
//
// The idea is that the web server parses the question text and replaces all
// format strings with an HTML tag triggering a request for this script:
//
//   <%name str %>  ->  <img src="/openexam/images/handler.php?type=name&data=enc">
//
// The enc value for parameter data is str encoded using urlencode(). The name 
// of parameter type is copied verbatime.
// 
// A request to this script checks if a handler exist. Handlers for different
// format types is stored under include/handler:
// 
// include/handler/
//            +-- smiles.inc     // class SmilesHandler
//            +-- latex.inc      // class LatexHandler
//           ...
// 
// Handlers are put in include files with the name of the type they are handling.
// The class name is type (first character upper case) followed by Handler, i.e.
// SmilesHandler handles <%smiles str %> format tags.
// 
// New handlers should implement the HandlerType interface and throw exceptions
// of type HandlerException on errors.
// 
// A cache is utilized to prevent excessive requests to external web services.
// Handlers don't need to bother with caching, the caching is handled automatic
// by this script.
// 

include "conf/config.inc";
include "include/handler/handler.inc";

// 
// Sanity check:
//
foreach (array("type", "data") as $name) {
        if (!isset($_REQUEST[$name])) {
                die(sprintf("missing parameter %s", $name));
        }
}

//
// Send from cache is file copy exists. Otherwise process the data first.
//
try {
        $factory = new HandlerFactory($_REQUEST['type']);
        $handler = $factory->create();

        $cached = new HandlerCache($_REQUEST['type'], $_REQUEST['data']);
        if (!$cached->exists()) {
                $handler->process($_REQUEST['data'], $cached->file);
        }
        $cached->send($handler->mime());
} catch (HandlerException $exception) {
        die($exception->getMessage());
}
?>
