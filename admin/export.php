<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   admin/export.php
// Author: Anders Lövgren
// Date:   2015-04-14
//

/**
 * Script for exporting exam as XML or JSON for import into other system.
 */
//
// The script should only be run in CLI mode.
//
if (isset($_SERVER['SERVER_ADDR'])) {
        die("This script should be runned in CLI mode.\n");
}
// 
// In case we are running from inside the admin directory:
// 
set_include_path(get_include_path() . PATH_SEPARATOR . "..");

// 
// Include external libraries:
// 
include "MDB2.php";
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
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Business logic:
// 
include "include/export.inc";
include "include/exam.inc";
include "include/teacher/manager.inc";
include "include/teacher/correct.inc";

class ExportApp
{

        private $debug = false;
        private $verbose = 0;
        private $format = "json";
        private $pretty = false;
        private $prog;
        private $exam;
        private $file;
        private $inc = 0;

        public function __get($name)
        {
                switch ($name) {
                        case "verbose":
                                return $this->verbose;
                        case "pretty":
                                return $this->pretty;
                }
        }

        private function error($msg)
        {
                die(sprintf("%s: %s\n", $this->prog, $msg));
        }

        public function main($argc, $argv)
        {
                $this->prog = basename($argv[0]);

                for ($i = 1; $i < $argc; ++$i) {
                        if (strchr($argv[$i], '=')) {
                                list($opt, $arg) = split('=', $argv[$i]);
                        } else {
                                list($opt, $arg) = array($argv[$i], null);
                        }

                        switch ($opt) {
                                // 
                                // Generic options:
                                // 
                                case '-e':
                                        $this->exam = $argv[++$i];
                                        break;
                                case '--exam':
                                        $this->exam = $arg;
                                        break;
                                case '-o':
                                        $this->file = $argv[++$i];
                                        break;
                                case '--file':
                                        $this->file = $arg;
                                        break;
                                case '-f':
                                        $this->format = $argv[++$i];
                                        break;
                                case '--format':
                                        $this->format = $arg;
                                        break;
                                case "--pretty":
                                case "-p":
                                        $this->pretty = true;
                                        break;
                                // 
                                // Include options:
                                // 
                                case '-P':
                                case '--project':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_PROJECT;
                                        break;
                                case '-T':
                                case '--topics':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_TOPICS;
                                        break;
                                case '-Q':
                                case '--questions':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_QUESTIONS;
                                        break;
                                case '-A':
                                case '--answers':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_ANSWERS;
                                        break;
                                case '-R':
                                case '--results':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_RESULTS;
                                        break;
                                case '-S':
                                case '--students':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_STUDENTS;
                                        break;
                                case '-X':
                                case '--roles':
                                        $this->inc |= OPENEXAM_EXPORT_INCLUDE_ROLES;
                                        break;
                                case '--all':
                                        $this->inc = OPENEXAM_EXPORT_INCLUDE_ALL;
                                        break;
                                case '--default':
                                        $this->inc = OPENEXAM_EXPORT_INCLUDE_DEFAULT;
                                        break;
                                case '--clone':
                                        $this->inc = OPENEXAM_EXPORT_INCLUDE_CLONE;
                                        break;
                                case '--correction':
                                        $this->inc = OPENEXAM_EXPORT_INCLUDE_CORRECTION;
                                        break;
                                // 
                                // Standard options:
                                // 
                                case '-h':
                                case '--help':
                                        $this->usage();
                                        exit(0);
                                case '-d':
                                case '--debug':
                                        $this->debug = true;
                                        break;
                                case '-v':
                                case '--verbose':
                                        $this->verbose++;
                                        break;
                                default:
                                        $this->error(sprintf("unknown option '%s'", $opt));
                        }
                }

                if ($this->inc == 0) {
                        $this->inc = OPENEXAM_EXPORT_INCLUDE_ALL;
                }

                // 
                // Process:
                // 
                $export = new Export($this->exam, $this->format, $this->inc);
                if (isset($this->file)) {
                        $export->write($this->file);
                } else {
                        $export->send();
                }
        }

        private function usage()
        {
                printf("%s - Export exam project\n", $this->prog);
                printf("\n");
                printf("Usage: %s -e num [-o file] [-h|--help]\n", $this->prog);
                printf("Generic Options:\n");
                printf("  -e,--exam=num:     The examination ID (from database).\n");
                printf("  -o,--file=name:    The output file (use stdout otherwise).\n");
                printf("  -f,--format=type:  Output format for listing (xml or json).\n");
                printf("  -p,--pretty:       Pretty print XML/JSON output.\n");
                printf("  -d,--debug:        Enable debug, can be used multiple times.\n");
                printf("  -v,--verbose:      Be more verbose, can be used multiple times.\n");
                printf("  -h,--help:         Show this help.\n");
                printf("Include Options:\n");
                printf("  -P,--project:      Include project settings.\n");
                printf("  -T,--topics:       Include topics.\n");
                printf("  -Q,--questions:    Include questions.\n");
                printf("  -A,--answers:      Include answers.\n");
                printf("  -R,--results:      Include results.\n");
                printf("  -S,--students:     Include students.\n");
                printf("  -X,--roles:        Include roles.\n");
                printf("Include Special:\n");
                printf("     --all:          Include all options.\n");
                printf("     --default:      Include default set of options.\n");
                printf("     --clone:        Include options for exam cloning.\n");
                printf("     --correction:   Include options for correction.\n");
                printf("\n");
                printf("Examples:\n");
                printf("  1. Export all options in JSON format:\n");
                printf("     php %s -e <id> --all --format=json --file=exam.json\n", $this->prog);
                printf("\n");
                printf("This script is part of the openexam-php project:\n");
                printf("  http://it.bmc.uu.se/andlov/proj/openexam/\n");
        }

}

$result = new ExportApp();
$result->main($_SERVER['argc'], $_SERVER['argv']);
