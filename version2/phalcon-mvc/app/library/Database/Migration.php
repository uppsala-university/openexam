<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Migration.php
// Created: 2014-09-04 13:47:55
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database;

/**
 * Database install and migration class.
 * 
 * This class is intended to be used for installing and upgrading the 
 * database using Phalcon\Migrations::run().
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Migration
{

        /**
         * Defines the upgrade order between database tables.
         * @var array 
         */
        private static $_order = array(
                'admins', 'teachers', 'schemainfo',
                'rooms', 'computers',
                'exams', 'contributors', 'decoders', 'examinators', 'invigilators',
                'topics', 'questions', 'locks',
                'students', 'answers', 'results'
        );

        /**
         * Run the migration.
         * 
         * @param \Phalcon\Config $config Using database and application settings.
         * @param string $version The version to upgrade to (default to latest).
         * @param bool $force Force database migration to version even if already migrated.
         */
        public static function run($config, $version = null, $force = false)
        {
                $options = array(
                        'migrationsDir' => $config->application->migrationsDir,
                        'config'        => $config,
                        'directory'     => $config->application->baseDir,
                        'version'       => $version
                );

                if ($force) {
                        $lockfile = sprintf("%s.phalcon/migration-version", $options['directory']);
                        if (file_exists($lockfile)) {
                                if (!unlink($lockfile)) {
                                        throw new Exception(sprintf("Failed remove lockfile %s in phalcon directory.", basename($lockfile)));
                                }
                        }
                }

                $exception = null;

                foreach (self::$_order as $table) {
                        try {
                                $options['tableName'] = $table;
                                \Phalcon\Migrations::run($options);
                        } catch (\Phalcon\Script\ScriptException $e) {
                                if (strstr($e->getMessage(), "Migration class was not found")) {
                                        printf("Notice: %s (trapped by %s)\n", $e->getMessage(), __CLASS__);
                                } else {
                                        $message = sprintf("Migration task for table %s failed", $table);
                                        if (isset($exception)) {
                                                $exception = new Exception($message, 0, $exception);
                                        } else {
                                                $exception = new Exception($message, 0, $e);
                                        }
                                }
                        }
                }
                if (isset($exception)) {
                        throw $exception;
                }
        }

}
