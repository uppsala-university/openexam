<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/exam/index.php
// Author: Anders Lövgren
// Date:   2010-12-13
//
// This script generates PHP code for creating the database and its tables
// by reading a MySQL dump (XML).
//
// This script is mainly useful if you got another RDBMS than MySQL and want
// to use it for OpenExam.
// 
// Requirements: 
// -----------------------------------------------------------------------
// 
// a) The SimpleXML extension must be loaded to run this script.
// b) The MDB2 package must be installed including its Manager module.
//
// Usage:
// -----------------------------------------------------------------------
// bash$> php schema-create-mdb2.php <mysql-dump>
//
// -----------------------------------------------------------------------
// IMPORTANT: This script is unsupported!
// -----------------------------------------------------------------------
//

class SchemaCreateApp
{

        private $filename;
        private $xml;

        public function __construct($filename)
        {
                $this->filename = $filename;
        }

        public function generate()
        {
                if (!file_exists($this->filename)) {
                        die(sprintf("The input file %s don't exist", $this->filename));
                }

                $this->xml = simplexml_load_file($this->filename);

                $this->includes();
                $this->setup();
                $this->database();
                $this->tables();
        }

        private function comment($str)
        {
                printf("//\n// %s:\n//\n", $str);
        }

        private function includes()
        {
                $this->comment("Include required files");

                printf("include 'MDB2.php';\n");
                printf("include 'conf/database.conf';\n");
                printf("include 'include/database.inc';\n");
                printf("\n");
        }

        private function setup()
        {
                $this->comment("Load the MDB2 manager module");
                printf("\$mdb2->loadModule('Manager');\n");
                printf("\n");
        }

        private function database()
        {
                $this->comment("Create the database");
                $name = $this->xml->database['name'];
                printf("\$mdb2->createDatabase('%s');\n", $name);
                printf("\n");
        }

        private function tables()
        {
                $tables = $this->xml->database->table_structure;
                foreach ($tables as $table) {
                        $def = array();     // table definition
                        $con = array();     // constraints

                        foreach ($table->field as $field) {
                                $name = (string) $field['Field'];
                                $null = (string) $field['Null'];
                                $key = (string) $field['Key'];
                                $default = isset($field['Default']) ? (string) $field['Default'] : null;
                                $type = (string) $field['Type'];
                                $size = 0;

                                $match = array();
                                if (strchr($type, "(")) {
                                        if (!preg_match("/(.*?)\((.*?)\)/", $field['Type'], $match)) {
                                                die(sprintf("Failed match type of %s.%s", $table['name'], $field['Field']));
                                        }
                                        $type = $match[1];
                                        $size = $match[2];
                                }
                                $def[$name] = array();

                                switch ($type) {
                                        case "int":
                                                $def[$name]['type'] = 'integer';
                                                $def[$name]['unsigned'] = 1;
                                                break;
                                        case "float":
                                                $def[$name]['type'] = 'float';
                                                break;
                                        case "varchar":
                                        case "char":
                                                $def[$name]['type'] = 'text';
                                                $def[$name]['length'] = $size;
                                                break;
                                        case "text":
                                                $def[$name]['type'] = 'text';
                                                break;
                                        case "datetime":
                                                $def[$name]['type'] = 'datetime';
                                                break;
                                        case "timestamp":
                                                $def[$name]['type'] = 'datetime';
                                                break;
                                        case "enum":
                                                $def[$name]['type'] = 'text';
                                                $def[$name]['length'] = strlen($size);
                                                break;
                                        default:
                                                die(print_r($field, true));
                                }
                                if ($null == "NO") {
                                        $def[$name]['notnull'] = 1;
                                }
                                if (isset($default)) {
                                        if (!isset($def[$name]['default'])) {
                                                $def[$name]['default'] = $default;
                                        }
                                }
                        }

                        $this->comment(sprintf("Create table %s", $table['name']));
                        $defs = array();
                        foreach ($def as $name => $data) {
                                $prop = array();
                                foreach ($data as $key => $val) {
                                        if (is_string($val)) {
                                                $prop[] = sprintf("'%s' => '%s'", $key, $val);
                                        } else {
                                                $prop[] = sprintf("'%s' => %d", $key, $val);
                                        }
                                }
                                $defs[] = sprintf("'%s' => array ( %s )", $name, implode(", ", $prop));
                        }
                        printf("\$definition = array ( %s );\n", implode(", ", $defs));
                        printf("\$mdb2->createTable('%s', \$definition);\n\n", $table['name']);

                        $this->comment("Add table constraints (primary and foreign keys)");
                        foreach ($table->key as $key) {
                                $name = (string) $key['Key_name'];
                                $column = (string) $key['Column_name'];
                                if ($name == "PRIMARY") {
                                        printf("\$definition = array ( 'primary' => true, 'fields' => array ('%s' => array()));\n", $column);
                                        printf("\$mdb2->createConstraint('%s', 'PRIMARY', \$definition);\n\n", $table['name']);
                                } else {
                                        $match = array();
                                        if (!preg_match("/^(.*?)_(.*?)$/", $column, $match)) {
                                                die("Failed match foreign key");
                                        }
                                        $fktbl = $match[1];
                                        $fkfld = $match[2];

                                        printf("\$definition = array ('foreign' => true, 'fields' => array ('field1name' => array( '%s' )), 'references' => array( 'table' => %s, 'fields' => array( 'field1name' => array ( '%s' ))));\n", $column, $fktbl, $fkfld);
                                        printf("\$mdb2->createConstraint('%s', '%s', \$definition);\n\n", $table['name'], $column);
                                }
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
if ($_SERVER['argc'] < 2) {
        die("Usage: php schema-create-mdb2.php <mysql-dump>\n");
}
if (!extension_loaded("SimpleXML")) {
        die("The required extension SimpleXML is not loaded.\n");
}

$app = new SchemaCreateApp($_SERVER['argv'][1]);
$app->generate();
?>
