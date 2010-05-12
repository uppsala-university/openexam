<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   admin/result.php
// Author: Anders Lövgren
// Date:   2010-05-12
// 

// 
// A script for generating examination result PDF's. This script can be
// useful for archiving purposes or for debugging the PDF generator by
// appending --debug to the command line options.
// 

//
// The script should only be run in CLI mode.
//
if(isset($_SERVER['SERVER_ADDR'])) {
        die("This script should be runned in CLI mode.\n");
}

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon and user interface support:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Business logic:
// 
include "include/teacher.inc";
include "include/exam.inc";
include "include/teacher/correct.inc";
include "include/teacher/manager.inc";
include "include/pdf.inc";

class ResultApp {
    private $debug = false;
    private $verbose = false;
    private $format = "pdf";
    private $prog;

    private function error($msg)
    {
	die(sprintf("%s: %s\n", $this->prog, $msg));
    }
    
    public function main($argc, $argv)
    {
	$this->prog = basename($argv[0]);
	
	//
	// Scan standard options first:
	//
	for($i = 1; $i < $argc; ++$i) {
	    list($opt, $arg) = split('=', $argv[$i]);
	    
	    switch($argv[$i]) {
	     case '-e':
		$this->exam = $argv[++$i];
		break;
	     case '--exam':
		$this->exam = $arg;
		break;
	     case '-s':
		$this->student = $argv[++$i];
		break;
	     case '--student':
		$this->student = $arg;
		break;
	     case '-f':
		$this->format = $argv[++$i];
		break;
	     case '--format':
		$this->format = $arg;
		break;
	     case '-o':
		$this->file = $argv[++$i];
		break;
	     case '--file':
		$this->file = $arg;
		break;
	     case '-p':
		$this->destdir = $argv[++$i];
		break;
	     case '--destdir':
		$this->destdir = $arg;
		break;
	     case '-h':
	     case '--help':
		self::showUsage();
		exit(0);
	     case '-d':
	     case '--debug':
		$this->debug = true;
		break;
	     case '-v':
	     case '--verbose':
		$this->verbose = true;
		break;
	     default:
		$this->error(sprintf("unknown option '%s'", $argv[$i]));
	    }
	}

	if(!isset($this->exam)) {
	    $this->error("missing -e option, see --help\n");
	}
	if(!isset($this->student) && !isset($this->destdir)) {
	    $this->error("either -s or -p option must be used, see --help\n");
	}
	if(isset($this->student) && isset($this->destdir)) {
	    $this->error("the -s option can't be used together with -p, see --help\n");
	}
	
	$result = new ResultPDF($this->exam);
	$result->setDebug($this->debug);
	$result->setFormat($this->format);
	
	if(isset($this->destdir)) {
	    $result->saveAll($this->destdir);
	} else {
	    if(isset($this->file)) {
		$result->save($this->student, $this->file);
	    } else {
		$result->send($this->student);
	    }
	}
    }
    
    private function showUsage()
    {
	printf("result.php - Generate result PDF/PS/HTML file(s)\n");
	printf("\n");
	printf("Usage: result.php [-e num [-p dir] | [-s num]] [-o file] [-f format] [-h|--help]\n");
	printf("Options:\n");
	printf("  -e,--exam=num:     The examination ID (from database).\n");
	printf("  -s,--student=num:  The student ID (from database).\n");
	printf("  -p,--destdir=path: Write result PDF's to directory.\n");
	printf("  -o,--file=name:    The output file (use stdout otherwise).\n");
	printf("  -f,--format=name:  Set output format (i.e. pdf, ps or html).\n");
	printf("  -d,--debug:        Enable debug, can be used multiple times.\n");
	printf("  -v,--verbose:      Be more verbose.\n");
	printf("  -h,--help:         Show this help.\n");
	printf("\n");
	printf("This script is part of the openexam-php project:\n");
	printf("  http://it.bmc.uu.se/andlov/proj/openexam/\n");
    }
    
}

$result = new ResultApp();
$result->main($_SERVER['argc'], $_SERVER['argv']);

?>
