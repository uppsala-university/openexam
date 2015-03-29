<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   admin/uppdok.php
// Author: Anders Lövgren
// Date:   2010-12-14
// 
// This script uses the currently used data sourced configured for UPPDOK.
//
//
// This script should only be runned from the command line:
//
if (isset($_SERVER['SERVER_ADDR'])) {
        die("This script should be runned in CLI mode.\n");
}

set_include_path('..');

include "conf/config.inc";
include "include/uppdok.inc";

class UppdokApp
{

        private $prog;
        private $courses = array();

        public function __construct($argc, $argv)
        {
                $this->prog = basename($argv[0]);

                if ($argc == 1) {
                        die(self::usage());
                }

                printf("argc: %d, argv: '%s'\n", $argc, implode(",", $argv));

                for ($i = 1; $i < $argc; $i++) {
                        switch (($args = $argv[$i])) {
                                case '-c':
                                        for (; $i < $argc; ++$i) {
                                                $course = $argv[++$i];
                                                if ($course[0] == '-' || strlen($course) == 0) {
                                                        $i--;
                                                        break;
                                                }
                                                array_push($this->courses, $course);
                                        }
                                        break;
                                default:
                                        die(sprintf("%s: (-) unknown option '%s'\n", $this->prog, $args));
                        }
                }
        }

        private function usage()
        {
                printf("%s - fetch UPPDOK data\n", $this->prog);
                printf("\n");
                printf("Usage: %s -c course ...\n", $this->prog);
                printf("Options:\n");
                printf("  -c course: One or more course codes to query (i.e. 1AB234)\n");
                printf("\n");
                printf("Copyright (c) 2010 Anders Lövgren, Compdept at BMC, Uppsala university.\n");
        }

        public function process()
        {
                $uppdok = new UppdokData();
                foreach ($this->courses as $course) {
                        printf("(+) Processing UPPDOK group %s:\n", $course);
                        $result = $uppdok->members($course);
                        printf(implode(",", $result));
                        printf("\n");
                }
        }

}

$uppdok = new UppdokApp($_SERVER['argc'], $_SERVER['argv']);
$uppdok->process();
?>
