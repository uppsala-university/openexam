<?php

//
// Copyright (C) 2010-2012 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/exam/index.php
// Author: Anders Lövgren
// Date:   2010-12-13
//
// This script upgrades the database schema gradual to the most recent version.
//

set_include_path(get_include_path() . PATH_SEPARATOR . "../../..");

//
// System check:
//
if (!file_exists("../../../conf/database.conf")) {
        $message = "The database.conf file was not found. ";
        $message .= "Are you really running this script from inside directory admin/sql/upgrade?";
        die($message);
}

include "MDB2.php";
include "conf/config.inc";
include "conf/database.conf";
include "include/database.inc";

class SchemaUpgradeException extends Exception
{

        public function __construct($message, $code = 0, $previous = null)
        {
                parent::__construct($message, $code, $previous);
        }

}

class SchemaUpgrade
{

        private $version = 0.0;         // Current schema version
        private $db;
        private $user;
        private $pass;
        private $force = false;
        private $debug = false;
        private $interactive = false;
        private $keepgoing = false;
        private $dryrun = false;

        public function __construct($argc, $argv)
        {
                $this->prog = basename($argv[0]);

                for ($i = 1; $i < $argc; ++$i) {
                        if (strchr($argv[$i], "=")) {
                                list($opt, $arg) = @split('=', $argv[$i]);
                        } else {
                                list($opt, $arg) = array($argv[$i], null);
                        }

                        switch ($opt) {
                                case "-a":
                                case "--appdef":
                                        $this->user = $GLOBALS['dsn']['username'];
                                        $this->pass = $GLOBALS['dsn']['password'];
                                        break;
                                case "-u":
                                        $this->user = $argv[++$i];
                                        break;
                                case "--user":
                                        $this->user = $arg;
                                        break;
                                case "-p":
                                        $this->pass = $argv[++$i];
                                        break;
                                case "--pass":
                                        $this->pass = $arg;
                                        break;
                                case "-i":
                                case "--interactive":
                                        $this->interactive = true;
                                        break;
                                case "-F":
                                case "--force":
                                        $this->force = true;
                                        break;
                                case "-k":
                                case "--keep-going":
                                        $this->keepgoing = true;
                                        break;
                                case "-D":
                                case "--dry-run":
                                        $this->dryrun = true;
                                        break;
                                case "-d":
                                case "--debug":
                                        $this->debug = true;
                                        break;
                                case "-h":
                                case "--help":
                                        $this->usage();
                                        exit(0);
                                default:
                                        $this->error(sprintf("unknown option '%s'", $opt));
                        }
                }

                if ($this->interactive) {
                        if (!isset($this->user)) {
                                $this->user = readline("> Username: ");
                        }
                        if (!isset($this->pass)) {
                                $this->pass = readline("> Password: ");
                        }
                }
                if (!isset($this->user) || strlen($this->user) == 0) {
                        $this->error("Missing database username, see --help");
                }
                if (!isset($this->pass) || strlen($this->pass) == 0) {
                        $this->error("Missing database password, see --help");
                }

                $GLOBALS['dsn']['username'] = $this->user;
                $GLOBALS['dsn']['password'] = $this->pass;

                if ($this->debug) {
                        $this->debug("Connecting to '%s@%s' as user '%s'.", $GLOBALS['dsn']['database'], $GLOBALS['dsn']['hostspec'], $GLOBALS['dsn']['username']);
                }
                if ($this->interactive) {
                        readline("> Press <enter> to begin database scheme upgrade: ");
                }

                $this->db = Database::getConnection();
                $this->getSchemaVersion();
        }

        private function usage()
        {
                printf("%s - upgrade database schema\n", $this->prog);
                printf("\n");
                printf("Usage: %s [options...]\n", $this->prog);
                printf("Options:\n");
                printf("  -a,--appdef:      Use the application database account for connection.\n");
                printf("  -u,--user=str:    Connect using str as username.\n");
                printf("  -p,--pass=str:    Connect using str as password.\n");
                printf("  -i,--interactive: Enable interactive mode (prompt)\n");
                printf("  -F,--force:       Force apply all updates.\n");
                printf("  -k,--keep-going:  Continue even on database error.\n");
                printf("  -D,--dry-run:     Don't modify, just print whats going to be executed.\n");
                printf("  -d,--debug:       Enable debug.\n");
                printf("  -h,--help:        Show this casual help.\n");
                printf("\n");
                printf("Copyright (c) 2010-2012 Anders Lövgren, Compdept at BMC, Uppsala university.\n");
        }

        private function error($msg)
        {
                die(sprintf("%s: %s\n", $this->prog, $msg));
        }

        private function debug()
        {
                $arr = func_get_args();
                $fmt = sprintf("(d) %s\n", array_shift($arr));
                vprintf($fmt, $arr);
        }

        private function info()
        {
                $arr = func_get_args();
                $fmt = sprintf("(i) %s\n", array_shift($arr));
                vprintf($fmt, $arr);
        }

        //
        // Read the current schema level:
        //
        private function getSchemaVersion()
        {
                $sql = "SELECT major, minor FROM schemainfo";
                $res = $this->db->query($sql);

                if (!PEAR::isError($res)) {
                        if (($row = $res->fetchRow())) {
                                $this->version = floatval(sprintf("%s.%s", $row['major'], $row['minor']));
                        }
                }
        }

        //
        // Process a single schema revision file.
        //
        private function process($filename, $version)
        {
                $content = file_get_contents($filename);
                $sqlstmt = explode(";", $content);

                //
                // Begin transaction and loop thru each SQL statement to execute:
                //
                $this->db->beginTransaction();
                printf("\nUpgrading to database schema version %0.1f:\n", $version);
                foreach ($sqlstmt as $sql) {
                        if (($sql = trim($sql)) != "") {
                                if ($this->debug) {
                                        $this->debug("SQL: %s", $sql);
                                }
                                if ($this->dryrun) {
                                        $this->info("Would have executed:\n%s", $sql);
                                } else {
                                        $res = $this->db->query($sql);
                                        if (PEAR::isError($res) && !$this->keepgoing) {
                                                throw new DatabaseException($res->getMessage());
                                        }
                                        print(".");
                                }
                        }
                }
                print(" done!\n");
                $this->db->commit();
        }

        //
        // Process all schema upgrades in order.
        // 
        public function upgrade()
        {
                $files = array(
                        "1.0" => "schema-upgrade-1.0.sql",
                        "1.1" => "schema-upgrade-1.1.sql",
                        "1.2" => "schema-upgrade-1.2.sql"
                );
                foreach ($files as $version => $filename) {
                        if ($this->force || $this->version < floatval($version)) {
                                $this->process($filename, floatval($version));
                        } elseif ($this->debug) {
                                $this->debug("Version %0.1f already applied (skipped)", $version);
                        }
                }
        }

}

//
// This script should only be runned from the command line:
//
if (isset($_SERVER['SERVER_ADDR'])) {
        die("This script should be runned in CLI mode.\n");
}

$app = new SchemaUpgrade($_SERVER['argc'], $_SERVER['argv']);
$app->upgrade();
?>
