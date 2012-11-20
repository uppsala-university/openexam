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

class OutputFormatException extends Exception
{
        
}

// 
// Data output formating interface.
// 
interface OutputFormat
{

        const TAB = "tab";
        const XML = "xml";

        function start();                       // output start

        function output($data);                 // output data

        function end();                         // output close

        static function create($type, $caller); // factory
}

abstract class OutputBase implements OutputFormat
{

        protected $caller;

        protected function __construct($caller)
        {
                $this->caller = $caller;
        }

        function start()
        {
                // no output by default
        }

        function end()
        {
                // no output by default
        }

}

abstract class OutputStudent extends OutputBase
{

        static function create($type, $caller)
        {
                switch ($type) {
                        case self::TAB:
                                return new OutputStudentTab($caller);
                        case self::XML:
                                return new OutputStudentXml($caller);
                        default:
                                throw new OutputFormatException(sprintf("unknown output type %s", $type));
                }
        }

}

class OutputStudentTab extends OutputStudent
{

        function output($student)
        {
                printf("[%d]\t%s (%s)\n", $student->getStudentID(), $student->getStudentUser(), $student->getStudentCode());
        }

}

class OutputStudentXml extends OutputStudent
{

        function start()
        {
                printf("<students>\n");
        }

        function output($student)
        {
                printf("  <student id=\"%d\">\n", $student->getStudentID());
                printf("    <user>%s</user>\n", $student->getStudentUser());
                printf("    <code>%s</code>\n", $student->getStudentCode());
                printf("  </student>\n");
        }

        function end()
        {
                printf("</students>\n");
        }

}

abstract class OutputExam extends OutputBase
{

        static function create($type, $caller)
        {
                switch ($type) {
                        case self::TAB:
                                return new OutputExamTab($caller);
                        case self::XML:
                                return new OutputExamXml($caller);
                        default:
                                throw new OutputFormatException(sprintf("unknown output type %s", $type));
                }
        }

}

class OutputExamTab extends OutputExam
{

        function output($exam)
        {
                printf("[%d]\t%s\n", $exam->getExamID(), $exam->getExamName());
                if ($this->caller->verbose) {
                        printf("\tStart:   %s\n", $exam->getExamStartTime());
                        printf("\tEnd:     %s\n", $exam->getExamEndTime());
                        printf("\tCreator: %s\n", $exam->getExamCreator());
                        printf("\tDecoded: %s\n", $exam->getExamDecoded() == 'Y' ? "yes" : "no");
                }
                if ($this->caller->verbose > 1) {
                        printf("\tCreated: %s\n", $exam->getExamCreated());
                        printf("\tUpdated: %s\n", $exam->getExamUpdated());
                }
                if ($this->caller->verbose) {
                        $grades = new ExamGrades($exam->getExamGrades());
                        printf("\tGrades:\n");
                        foreach ($grades->getGrades() as $name => $score) {
                                printf("\t\t%d\t(%s)\n", $score, $name);
                        }
                }
                if ($this->caller->verbose > 2) {
                        printf("\tDescription:\n\t\t%s\n", str_replace("\n", "\n\t\t", $exam->getExamDescription()));
                }
                if ($this->caller->verbose) {
                        printf("\n");
                }
        }

}

class OutputExamXml extends OutputExam
{

        function start()
        {
                printf("<exams>\n");
        }

        function end()
        {
                printf("</exams>\n");
        }

        function output($exam)
        {
                printf("  <exam id=\"%d\">\n", $exam->getExamID());
                printf("    <name>%s</name>\n", $exam->getExamName());
                if ($this->caller->verbose) {
                        printf("    <start>%s</start>\n", $exam->getExamStartTime());
                        printf("    <end>%s</end>\n", $exam->getExamEndTime());
                        printf("    <creator>%s</creator>\n", $exam->getExamCreator());
                        printf("    <decoded>%s</decoded>\n", $exam->getExamDecoded() == 'Y' ? "yes" : "no");
                }
                if ($this->caller->verbose > 1) {
                        printf("    <created>%s</reated>\n", $exam->getExamCreated());
                        printf("    <updated>%s</updated>\n", $exam->getExamUpdated());
                }
                if ($this->caller->verbose) {
                        $grades = new ExamGrades($exam->getExamGrades());
                        printf("    <grades>\n");
                        foreach ($grades->getGrades() as $name => $score) {
                                printf("      <%s>%d</%s>\n", $name, $score, $name);
                        }
                        printf("    </grades>\n");
                }
                if ($this->caller->verbose > 2) {
                        printf("    <description>%s</description>\n", htmlspecialchars($exam->getExamDescription()));
                }
                printf("  </exam>\n");
        }

}

class ResultApp
{

        private $list = false;
        private $debug = false;
        private $verbose = 0;
        private $output = "pdf";
        private $format = "tab";
        private $prog;
        private $file;
        private $user;
        private $exam;
        private $student;
        private $destdir;
        private $formatter;

        public function __get($name)
        {
                switch ($name) {
                        case "verbose":
                                return $this->verbose;
                }
        }

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
                        if (strchr($argv[$i], '=')) {
                                list($opt, $arg) = split('=', $argv[$i]);
                        } else {
                                list($opt, $arg) = array($argv[$i], null);
                        }

                        switch ($opt) {
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
                                case '-O':
                                        $this->output = $argv[++$i];
                                        break;
                                case '--output':
                                        $this->output = $arg;
                                        break;
                                case '-o':
                                        $this->file = $argv[++$i];
                                        break;
                                case "--format":
                                        $this->format = $arg;
                                        break;
                                case "-f":
                                        $this->format = $argv[++$i];
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

                if ($this->list) {
                        try {
                                if (isset($this->exam)) {
                                        $this->formatter = OutputStudent::create($this->format, $this);
                                        $this->listStudents();
                                } else {
                                        $this->formatter = OutputExam::create($this->format, $this);
                                        $this->listExams();
                                }
                                return;
                        } catch (OutputFormatException $exception) {
                                $this->error($exception->getMessage());
                        }
                }

                if (!isset($this->exam)) {
                        $this->error("missing -e option, see --help");
                }
                if (!isset($this->student) && !isset($this->destdir)) {
                        $this->error("either -s or -p option must be used, see --help");
                }
                if (isset($this->student) && isset($this->destdir)) {
                        $this->error("the -s option can't be used together with -p, see --help");
                }

                $result = new ResultPDF($this->exam);
                $result->setDebug($this->debug);
                $result->setFormat($this->output);

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

        private function usage()
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
                printf("  -O,--output=name:  Set output format (i.e. pdf, ps or html) for result.\n");
                printf("  -f,--format=type:  Output format for listing (xml or tab).\n");
                printf("  -d,--debug:        Enable debug, can be used multiple times.\n");
                printf("  -v,--verbose:      Be more verbose, can be used multiple times.\n");
                printf("  -h,--help:         Show this help.\n");
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
                if ($this->user) {
                        $exams = Manager::getExams($this->user);
                } else {
                        $exams = Exam::getExamList();
                }

                $this->formatter->start();
                foreach ($exams as $exam) {
                        @$this->formatter->output($exam);
                }
                $this->formatter->end();
        }

        //
        // List all students on the exam.
        //
        private function listStudents()
        {
                $manager = new Manager($this->exam);
                $students = $manager->getStudents();

                $this->formatter->start();
                foreach ($students as $student) {
                        $this->formatter->output($student);
                }
                $this->formatter->end();
        }

}

$result = new ResultApp();
$result->main($_SERVER['argc'], $_SERVER['argv']);
?>
