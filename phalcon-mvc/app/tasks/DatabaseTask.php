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
class DatabaseTask extends MainTask
{

        /**
         * Display usage information.
         */
        public function helpAction()
        {
                printf("Usage: --migrate [--version=x.y.z] [--force]\n");
                printf("       --status\n");
                printf("\n");
                printf("Options:\n");
                printf("  --migrate:       Perform database migration (use latest version by default).\n");
                printf("  --generate:      Generate database migration (automatic version if not given).\n");
                printf("  --version=x.y.z: Version of migration or generation task.\n");
                printf("  --force:         Force migration even if already applied.\n");
                printf("  --status:        Display current migration status and exit.\n");
                printf("  --verbose:       Be more verbose.\n");
                printf("\n");
                printf("Examples:\n");
                printf("  # Generate migration, overwrite if existing:\n");
                printf("  --generate --verbose --version=2.0.0 --force\n");
                printf("\n");
                printf("  # Migrate database to latest:\n");
                printf("  --migrate --verbose\n");
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
                        printf("Starting database migration to version '%s':\n", isset($options['version']) ? $options['version'] : 'latest');
                        printf("-------------------------------------------------\n");
                        printf("    Type: %s\n", $this->config->database->adapter);
                        printf("    Name: %s\n", $this->config->database->dbname);
                        printf("    User: %s\n", $this->config->database->username);
                        printf("    Host: %s\n", $this->config->database->host);
                        printf("  Source: %s\n", $this->config->application->migrationsDir);
                        printf("\n");
                }

                Migration::run($this->config, $options['version'], $options['force']);
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
                        printf("Generate database migration as version '%s':\n", isset($options['version']) ? $options['version'] : '<automatic>');
                        printf("-------------------------------------------------\n");
                        printf("    Type: %s\n", $this->config->database->adapter);
                        printf("    Name: %s\n", $this->config->database->dbname);
                        printf("    User: %s\n", $this->config->database->username);
                        printf("    Host: %s\n", $this->config->database->host);
                        printf("\n");
                }

                \Phalcon\Migrations::generate($options);
        }

        /**
         * Migrate status action.
         * @param array $params Optional parameters ('verbose').
         */
        public function statusAction($params = array())
        {
                $file = sprintf("%s/.phalcon/migration-version", $this->config->application->baseDir);

                if (in_array('verbose', $params)) {
                        printf("Checking for status info in %s\n", $file);
                }
                if (file_exists($file)) {
                        printf("Current database migration version: %s\n", file_get_contents($file));
                } else {
                        printf("No database migration status found.\n");
                }
        }

}
