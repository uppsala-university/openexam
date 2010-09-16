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
include "include/ldap.inc";
include "include/scoreboard.inc";

class ResultApp
{

        private $list = false;
        private $debug = false;
        private $verbose = 0;
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
                for ($i = 1; $i < $argc; ++$i) {
                        list($opt, $arg) = split('=', $argv[$i]);

                        switch ($argv[$i]) {
                                case '-l':
                                case '--list':
                                        $this->list = true;
                                        break;
                                case '-u':
                                        $this->user = $argv[++$i];
                                        break;
                                case '--user':
                                        $this->user = $arg;
                                        break;
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
                                        $this->verbose++;
                                        break;
                                default:
                                        $this->error(sprintf("unknown option '%s'", $argv[$i]));
                        }
                }

                if ($this->list) {
                        if (!isset($this->user)) {
                                $this->error("the -l option requires the -u option, see --help\n");
                        }
                        if (isset($this->exam)) {
                                $this->listStudents();
                        } else {
                                $this->listExams();
                        }
                        return;
                }

                if (!isset($this->exam)) {
                        $this->error("missing -e option, see --help\n");
                }
                if (!isset($this->student) && !isset($this->destdir)) {
                        $this->error("either -s or -p option must be used, see --help\n");
                }
                if (isset($this->student) && isset($this->destdir)) {
                        $this->error("the -s option can't be used together with -p, see --help\n");
                }

                $result = new ResultPDF($this->exam);
                $result->setDebug($this->debug);
                $result->setFormat($this->format);

                if (isset($this->destdir)) {
                        $result->saveAll($this->destdir);
                } else {
                        if (isset($this->file)) {
                                $result->save($this->student, $this->file);
                        } else {
                                $result->send($this->student);
                        }
                }
        }

        private function showUsage()
        {
                printf("%s - Generate result PDF/PS/HTML file(s)\n", $this->prog);
                printf("\n");
                printf("Usage: %s [-e num [-p dir] | [-s num]] [-o file] [-f format] [-h|--help]\n", $this->prog);
                printf("Options:\n");
                printf("  -l,--list:         List all exams (*). Use with -e to list all students.\n");
                printf("  -u,--user=name:    Work as this user.\n");
                printf("  -e,--exam=num:     The examination ID (from database).\n");
                printf("  -s,--student=num:  The student ID (from database).\n");
                printf("  -p,--destdir=path: Write result PDF's to directory.\n");
                printf("  -o,--file=name:    The output file (use stdout otherwise).\n");
                printf("  -f,--format=name:  Set output format (i.e. pdf, ps or html).\n");
                printf("  -d,--debug:        Enable debug, can be used multiple times.\n");
                printf("  -v,--verbose:      Be more verbose, can be used multiple times.\n");
                printf("  -h,--help:         Show this help.\n");
                printf("\n");
                printf("Note:\n");
                printf("  *) The -l option requires the -u option.\n");
                printf("\n");
                printf("Examples:\n");
                if ($this->verbose) {
                        printf("  1. # Verbosly list all exams for this user:\n");
                        printf("     php %s -l -u user -v -v\n", $this->prog);
                        printf("\n");
                        printf("  2. # List all students on exam with ID 4:\n");
                        printf("     php %s -l -u user -e 4\n", $this->prog);
                        printf("\n");
                        printf("  3. # Generate result PDF for all students on exam with ID 4:\n");
                        printf("     php %s -e 4 -p resdir -f pdf\n", $this->prog);
                        printf("\n");
                        printf("  4. # Print result for student with ID 7 in HTML on stdout:\n");
                        printf("     php %s -e 4 -s 7 -f html\n", $this->prog);
                } else {
                        printf("  Use 'php %s -v -h' to display some examples.\n", $this->prog);
                }
                printf("\n");
                printf("This script is part of the openexam-php project:\n");
                printf("  http://it.bmc.uu.se/andlov/proj/openexam/\n");
        }

        //
        // List all exams where selected user is manager.
        //
        private function listExams()
        {
                $exams = Manager::getExams($this->user);
                foreach ($exams as $exam) {
                        printf("[%d]\t%s\n", $exam->getExamID(), utf8_decode($exam->getExamName()));
                        if ($this->verbose) {
                                printf("\tStart:   %s\n", $exam->getExamStartTime());
                                printf("\tEnd:     %s\n", $exam->getExamEndTime());
                                printf("\tCreator: %s\n", $exam->getExamCreator());
                                printf("\tDecoded: %s\n", $exam->getExamDecoded() == 'Y' ? "yes" : "no");
                        }
                        if ($this->verbose > 1) {
                                printf("\tCreated: %s\n", $exam->getExamCreated());
                                printf("\tUpdated: %s\n", $exam->getExamUpdated());
                        }
                        if ($this->verbose) {
                                $grades = new ExamGrades($exam->getExamGrades());
                                printf("\tGrades:\n");
                                foreach ($grades->getGrades() as $name => $score) {
                                        printf("\t\t%d\t(%s)\n", $score, $name);
                                }
                        }
                        if ($this->verbose > 2) {
                                printf("\tDescription:\n\t\t%s\n", str_replace("\n", "\n\t\t", utf8_decode($exam->getExamDescription())));
                        }
                        if ($this->verbose) {
                                printf("\n");
                        }
                }
        }

        //
        // List all students on the exam.
        //
        private function listStudents()
        {
                $manager = new Manager($this->exam);
                $students = $manager->getStudents();
                foreach ($students as $student) {
                        printf("[%d]\t%s (%s)\n", $student->getStudentID(), $student->getStudentUser(), $student->getStudentCode());
                }
        }

}

$result = new ResultApp();
$result->main($_SERVER['argc'], $_SERVER['argv']);
?>
