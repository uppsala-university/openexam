<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DatabaseTask.php
// Created: 2014-09-08 19:22:32
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Database\Migration;

/**
 * Database task.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DatabaseTask extends MainTask implements TaskInterface
{

        public static function getUsage()
        {
                return array(
                        'header'   => 'Database install or migration (upgrade/downgrade) task.',
                        'action'   => '--database',
                        'usage'    => array(
                                '--install',
                                '--generate',
                                '--migrate [--version=x.y.z]',
                                '--status'
                        ),
                        'options'  => array(
                                '--install'       => 'Install the database (same as --migrate without --version).',
                                '--generate'      => 'Generate database migration (automatic version if not given).',
                                '--migrate'       => 'Perform database migration (to latest version by default).',
                                '--version=x.y.z' => 'Version of migration or generation task.',
                                '--force'         => 'Force action even if already applied.',
                                '--status'        => 'Display current migration status and exit.',
                                '--verbose'       => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Generate migration, overwrite if existing',
                                        'command' => '--generate --verbose --version=2.0.0 --force'
                                ),
                                array(
                                        'descr'   => 'Migrate database to latest',
                                        'command' => '--migrate --verbose'
                                )
                        )
                );
        }

        /**
         * Display usage information.
         */
        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        /**
         * Install database action.
         * @param array $params Optional parameters ('force', 'verbose').
         */
        public function installAction($params = array())
        {
                if (in_array('version', $params)) {
                        throw new Exception("Unexpected param 'version'");
                }
                $this->dispatcher->forward(
                    array(
                            'task'   => 'database',
                            'action' => 'migrate',
                            'params' => array($params)
                    )
                );
        }

        /**
         * Database migrate action.
         * @param array $params Optional parameters ('version', 'force', 'verbose').
         */
        public function migrateAction($params = array())
        {
                $options = array(
                        'verbose' => false,
                        'version' => null,
                        'force'   => false
                );

                for ($i = 0; $i < count($params); ++$i) {
                        if ($params[$i] == 'version') {
                                $options['version'] = $params[++$i];
                        }
                        if ($params[$i] == 'force') {
                                $options['force'] = true;
                        }
                        if ($params[$i] == 'verbose') {
                                $options['verbose'] = true;
                        }
                }

                if ($options['verbose']) {
                        $this->flash->notice(sprintf("Starting database migration to version '%s':", isset($options['version']) ? $options['version'] : 'latest'));
                        $this->flash->notice(sprintf("-------------------------------------------------"));
                        $this->flash->notice(sprintf("    Type: %s", $this->config->database->adapter));
                        $this->flash->notice(sprintf("    Name: %s", $this->config->database->dbname));
                        $this->flash->notice(sprintf("    User: %s", $this->config->database->username));
                        $this->flash->notice(sprintf("    Host: %s", $this->config->database->host));
                        $this->flash->notice(sprintf("  Source: %s", $this->config->application->migrationsDir));
                        $this->flash->write();
                }

                Migration::run($this->config, $options['version'], $options['force']);
                $this->flash->success("Database migration action completed successful.");
        }

        /**
         * Generate database migration.
         * @param array $params Optional parameters ('version', 'verbose').
         */
        public function generateAction($params = array())
        {
                $options = array(
                        'migrationsDir'   => $this->config->application->migrationsDir,
                        'config'          => $this->config,
                        'directory'       => $this->config->application->baseDir,
                        'verbose'         => false,
                        'originalVersion' => null,
                        'force'           => false,
                        'tableName'       => 'all',
                        'exportData'      => null
                );

                for ($i = 0; $i < count($params); ++$i) {
                        if ($params[$i] == 'version') {
                                $options['originalVersion'] = $params[++$i];
                        }
                        if ($params[$i] == 'force') {
                                $options['force'] = true;
                        }
                        if ($params[$i] == 'verbose') {
                                $options['verbose'] = true;
                        }
                }

                if ($options['verbose']) {
                        $this->flash->notice(sprintf("Generate database migration as version '%s':", isset($options['version']) ? $options['version'] : '<automatic>'));
                        $this->flash->notice(sprintf("-------------------------------------------------"));
                        $this->flash->notice(sprintf("    Type: %s", $this->config->database->adapter));
                        $this->flash->notice(sprintf("    Name: %s", $this->config->database->dbname));
                        $this->flash->notice(sprintf("    User: %s", $this->config->database->username));
                        $this->flash->notice(sprintf("    Host: %s", $this->config->database->host));
                        $this->flash->write();
                }

                \Phalcon\Migrations::generate($options);
                $this->flash->success("Database migration generated successful.");
        }

        /**
         * Migrate status action.
         * @param array $params Optional parameters ('verbose').
         */
        public function statusAction($params = array())
        {
                $file = sprintf("%s/.phalcon/migration-version", $this->config->application->baseDir);

                if (in_array('verbose', $params)) {
                        $this->flash->notice(sprintf("Checking for status info in %s\n", $file));
                }
                if (file_exists($file)) {
                        $this->flash->success(sprintf("Current database migration version: %s", file_get_contents($file)));
                } else {
                        $this->flash->notice(sprintf("No database migration status found."));
                }
        }

}
