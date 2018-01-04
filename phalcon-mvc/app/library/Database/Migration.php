<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Migration.php
// Created: 2014-09-04 13:47:55
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database;

use Phalcon\Config;
use Phalcon\Migrations;
use Phalcon\Script\ScriptException;

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
         * @param Config $config Using database and application settings.
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
                                Migrations::run($options);
                        } catch (ScriptException $e) {
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
