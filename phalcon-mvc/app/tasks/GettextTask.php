<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    GettextTask.php
// Created: 2014-09-19 05:14:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Globalization\Translate\Gettext\Setup;

/**
 * GNU gettext task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class GettextTask extends MainTask implements TaskInterface
{

        /**
         * Command line options.
         * @var array 
         */
        private $_options;
        /**
         * The gettext setup.
         * @var OpenExam\Library\Locale\Gettext\Setup
         */
        private $_setup;

        /**
         * Initializer hook.
         */
        public function initialize()
        {
                if (!extension_loaded('gettext')) {
                        throw new Exception('The GNU gettext extension is not loaded.');
                }
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'GNU gettext task (I18N).',
                        'action'   => '--gettext',
                        'usage'    => array(
                                '[--update] [--merge] [--compile] | [--all] [--module=name]',
                                '--initialize --locale=name [--module=name]',
                                '--list',
                                '--clean'
                        ),
                        'options'  => array(
                                '--update'      => 'Extract gettext strings from source.',
                                '--merge'       => 'Merge message catalog and template.',
                                '--compile'     => 'Compile message catalog to binary format.',
                                '--clean'       => 'Clean compiled binary files.',
                                '--all'         => 'Alias for --update, --merge and --compile',
                                '--initialize'  => 'Initialize new locale directory.',
                                '--list'        => 'Show modules defined in configuration.',
                                '--locale=name' => 'The locale name (e.g. sv_SE).',
                                '--module=name' => 'Set module for current task.',
                                '--force'       => 'Force action even if already applied.',
                                '--verbose'     => 'Be more verbose.',
                                '--dry-run'     => 'Just print whats going to be done.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Force update, merge and compile in one step:',
                                        'command' => '--all --force'
                                ),
                                array(
                                        'descr'   => 'Same as above:',
                                        'command' => '--update --merge --compile --force'
                                ),
                                array(
                                        'descr'   => 'Initialize swedish locale for the student module:',
                                        'command' => '--initialize --module=student --locale=sv_SE.UTF-8'
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

        public function allAction($params = array())
        {
                $this->setOptions($params, 'all');
                $this->_options['update'] = true;
                $this->_options['merge'] = true;
                $this->_options['compile'] = true;
                $this->perform();
        }

        public function cleanAction($params = array())
        {
                $this->setOptions($params, 'clean');
                $this->perform();
        }

        public function compileAction($params = array())
        {
                $this->setOptions($params, 'compile');
                $this->perform();
        }

        public function initializeAction($params = array())
        {
                $this->setOptions($params, 'initialize');
                $this->perform();
        }

        public function listAction($params = array())
        {
                $this->setOptions($params, 'list');
                $this->perform();
        }

        public function mergeAction($params = array())
        {
                $this->setOptions($params, 'merge');
                $this->perform();
        }

        public function updateAction($params = array())
        {
                $this->setOptions($params, 'update');
                $this->perform();
        }

        /**
         * Perform all actions.
         */
        private function perform()
        {
                $translate = $this->config->translate;

                if ($this->_options['list']) {
                        foreach ($translate as $module => $params) {
                                $this->flash->notice(sprintf("%s (module)", $module));
                                if ($this->_options['verbose']) {
                                        foreach ($params as $type => $data) {
                                                $this->flash->notice(sprintf("\t+-- %s", $type));
                                                foreach ($data as $path) {
                                                        $this->flash->notice(sprintf("\t\t+-- %s", $path));
                                                }
                                        }
                                }
                        }
                }

                if ($this->_options['initialize']) {
                        if (!$this->_options['locale']) {
                                throw new Exception("Missing --locale argument");
                        }
                        if ($this->_options['module'] && $translate->get($this->_options['module']) == null) {
                                throw new Exception(sprintf("Unknown module %s, see --list", $this->_options['module']));
                        }
                }

                $commands = array();
                $modules = array();

                if ($this->_options['initialize']) {
                        $commands[] = 'initialize';
                }
                if ($this->_options['clean']) {
                        $commands[] = 'clean';
                }
                if ($this->_options['update']) {
                        $commands[] = 'update';
                }
                if ($this->_options['merge']) {
                        $commands[] = 'merge';
                }
                if ($this->_options['compile']) {
                        $commands[] = 'compile';
                }

                if ($this->_options['module']) {
                        $modules[] = $this->_options['module'];
                } else {
                        foreach (array_keys($translate->toArray()) as $module) {
                                $modules[] = $module;
                        }
                }

                $setup = new Setup($this, $this->_options);

                foreach ($commands as $command) {
                        foreach ($modules as $module) {
                                $setup->setDomain($module);
                                $setup->$command();
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
                $this->_options = array('verbose' => false, 'force' => false, 'dry-run' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'all', 'clean', 'compile', 'merge', 'update', 'initialize', 'list', 'locale', 'module');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        $this->_options[$option] = false;
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->_options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->_options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->_options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }
        }

}
