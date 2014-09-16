<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelTask.php
// Created: 2014-09-10 02:59:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Model\ModelManager;

/**
 * Models task.
 * 
 * For successful operations by this class, the following conditions 
 * must be met:
 * 
 *   1. The table name is plurals (e.g. admins).
 * 
 *   2. The name of generated models (by phalcon/devtools) are pluralis 
 *      (e.g. Admins). The stems from 1.
 * 
 *   3. The system models are singularis (e.g. Admin).
 * 
 *   4. Code in existing models (the one to update) can have class level 
 *      docblock, properties, methods and lines of source code tagged 
 *      with @preserve annotations. These code pieces are verbatime copied 
 *      to the updated model in the patch action.
 * 
 * Notice: 
 * 
 *   *) Keep models under version control (e.g. Subversion or GIT). 
 *   *) Remember to format the source after update.
 *   *) Use --backup action before --sync to be safe.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelTask extends MainTask
{

        /**
         * @var ModelManager 
         */
        private $manager;
        /**
         * Command line options.
         * @var array 
         */
        private $options;

        /**
         * Callback to initialize this task.
         */
        public function initialize()
        {
                $this->manager = new ModelManager();
                $this->manager->setDI($this->getDI());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Task for maintainance of the data model.',
                        'action'   => '--model',
                        'usage'    => array(
                                '--sync [--backup=dir]',
                                '[--update] [--clean] [--create] [--backup=dir]'
                        ),
                        'options'  => array(
                                '--update'        => 'Update all models.',
                                '--create'        => 'Create new models.',
                                '--clean'         => 'Cleanup in models.',
                                '--backup[=path]' => 'Backup model to directory path or cache directory',
                                '--sync'          => 'Alias for --update --clean --create',
                                '--force'         => 'Force model update.',
                                '--verbose'       => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Cleanup and create models before update',
                                        'command' => '--update --clean --create'
                                ),
                                array(
                                        'descr'   => 'Same as previous',
                                        'command' => '--update=force',
                                ),
                                array(
                                        'descr'   => 'Same as previous',
                                        'command' => '--update --force'
                                ),
                                array(
                                        'descr'   => 'Complete sync with backup to cache directory',
                                        'command' => '--update --backup --clean --create'
                                ),
                                array(
                                        'descr'   => 'Same as previous, but without backup first',
                                        'command' => '--sync'
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
         * Synchronize all models.
         * 
         * This action is an convenience action that will implicit clean, 
         * create and update models.
         * 
         * @param array $params Task action parameters.
         */
        public function syncAction($params = array())
        {
                $this->setOptions($params, 'sync');
                $this->options['clean'] = true;
                $this->options['create'] = true;
                $this->options['update'] = true;
                $this->perform();
        }

        /**
         * Update all models. Calls clean and create actions as pre-process 
         * tasks.
         * @param array $params Task action parameters.
         */
        public function updateAction($params = array())
        {
                $this->setOptions($params, 'update');
                if ($this->options['force']) {
                        $this->options['clean'] = $this->options['create'] = true;
                }
                $this->perform();
        }

        /**
         * Create all models.
         * @param array $params Task action parameters.
         */
        public function createAction($params = array())
        {
                $this->setOptions($params, 'create');
                if ($this->options['force']) {
                        $this->options['clean'] = true;
                }
                $this->perform();
        }

        /**
         * Cleanup generated models.
         * @param array $params Task action parameters.
         */
        public function cleanAction($params = array())
        {
                $this->setOptions($params, 'clean');
                $this->perform();
        }

        /**
         * Backup all models.
         * @param array $params Task action parameters.
         */
        public function backupAction($params = array())
        {
                $this->setOptions($params, 'backup');
                $this->perform();
        }

        /**
         * Perform all actions.
         */
        private function perform()
        {
                if ($this->options['backup']) {
                        $this->manager->backup($this->options);
                }
                if ($this->options['clean']) {
                        $this->manager->clean($this->options);
                }
                if ($this->options['create']) {
                        $this->manager->create($this->options);
                }
                if ($this->options['update']) {
                        $this->manager->update($this->options);
                }
        }

        /**
         * Get next index directory.
         * @param string $root The root path.
         */
        private static function getDirectory($root)
        {
                for ($i = 1;; ++$i) {
                        $path = sprintf("%s/%s_%04d", $root, date('Ymd'), $i);
                        if (!file_exists($path)) {
                                return $path;
                        }
                }
        }

        /**
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->options = array('verbose' => false, 'force' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'backup', 'clean', 'create', 'update', 'sync');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        $this->options[$option] = false;
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }

                // 
                // Some special handling for --key options taking a value:
                // 
                if ($this->options['backup']) {
                        if (is_bool($this->options['backup'])) {
                                $backupdir = sprintf("%s/models/backup", $this->config->application->cacheDir);
                                $backupdir = self::getDirectory($backupdir);
                                $this->options['backup'] = $backupdir;
                        } elseif ($this->options['backup'][0] != DIRECTORY_SEPARATOR) {
                                $this->options['backup'] = sprintf("%s/%s", getcwd(), $this->options['backup']);
                        }
                }
        }

}