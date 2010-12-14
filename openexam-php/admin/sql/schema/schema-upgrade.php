<?php

//
// Copyright (C) 2010 Computing Department BMC,
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

        public function __construct()
        {
                $this->db = Database::getConnection();
                $this->getSchemaVersion();
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
                                $this->version = float(sprintf("%s.%s", $row['major'], $row['minor']));
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
                                $res = $this->db->query($sql);
                                if (PEAR::isError($res)) {
                                        throw new DatabaseException($res->getMessage());
                                }
                        }
                        print(".");
                }
                print(" done!\n");
                $this->db->commit();
        }

        //
        // Process all schema upgrades in order.
        // 
        public function upgrade()
        {
                $files = array(1.0 => "schema-upgrade-1.0.sql");
                foreach ($files as $version => $filename) {
                        if ($this->version < $version) {
                                $this->process($filename, $version);
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

//
// Set global connection parameters:
//
printf("user: ");
$GLOBALS['dsn']['username'] = trim(fgets(STDIN));
printf("pass: ");
$GLOBALS['dsn']['password'] = trim(fgets(STDIN));

$app = new SchemaUpgrade();
$app->upgrade();
?>
