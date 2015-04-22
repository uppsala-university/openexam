<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Import.php
// Created: 2015-04-14 23:48:19
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import;

/**
 * The interface for concrete import classes. 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Import
{

        /**
         * Include project properties in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_PROJECT = 1;
        /**
         * Include topics in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_TOPICS = 2;
        /**
         * Include questions in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_QUESTIONS = 4;
        /**
         * Include answers in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_ANSWERS = 8;
        /**
         * Include result in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_RESULTS = 16;
        /**
         * Include roles in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_ROLES = 32;
        /**
         * Include students in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_STUDENTS = 64;
        /**
         * Include resources in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_RESOURCES = 128;
        /**
         * Include access rules in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_ACCESS = 256;
        /**
         * Default include options (project, topics and questions) in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_DEFAULT = 7;
        /**
         * Include all options in import.
         */
        const OPENEXAM_IMPORT_INCLUDE_ALL = 511;
        /**
         * Supported versions of native export/import formats.
         */
        const OPENEXAM_IMPORT_FORMAT_VERSION = '6073';

        /**
         * Prepare import.
         * 
         * This function is called by the import service consumer before 
         * calling read() to start the actual import. Override in child class
         * if house keeping need to be done.
         */
        function open();

        /**
         * Parse current opened import file and save data to exam.
         */
        function read();

        /**
         * Delegate insert to inserter object.
         * @param ImportInsert $inserter The inserter object.
         */
        function insert($inserter);

        /**
         * Finish import.
         * 
         * This function is called by the import service consumer when
         * read() has finixhed the import. Override in child class if house
         * keeping need to be done.
         */
        function close();
        
        /**
         * Get import data.
         * @return ImportData
         */
        function getData();
}
