<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SoapServiceTask.php
// Created: 2014-10-16 11:03:34
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Security\Capabilities;
use OpenExam\Library\WebService\Soap\ServiceGenerator;

/**
 * SOAP service task.
 * 
 * Provides command line interface for generating SOAP service handle classes.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SoapServiceTask extends MainTask
{

        /**
         * The capabilities object.
         * @var Capabilities 
         */
        private $capabilities;
        /**
         * Command line options.
         * @var array 
         */
        private $options;

        /**
         * Initializer hook.
         */
        public function initialize()
        {
                $this->capabilities = new Capabilities(require(CONFIG_DIR . '/access.def'));
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'SOAP service generator.',
                        'action'   => '--soap-service',
                        'usage'    => array(
                                '--generate [--role=role|--core|--all] [--with-handle] [--save=path]'
                        ),
                        'options'  => array(
                                '--generate'    => 'Generate SOAP service.',
                                '--role=role'   => 'Create SOAP service for role.',
                                '--with-handle' => 'Use handle object in method calls.',
                                '--save=path'   => 'Write class to file or directory.',
                                '--verbose'     => 'Be more verbose.',
                                '--dry-run'     => 'Just print whats going to be done.',
                                '--all'         => 'Alias for --role=all'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Create SOAP service for student role',
                                        'command' => '--generate --role=student'
                                ),
                                array(
                                        'descr'   => 'Create SOAP services for all roles',
                                        'command' => '--generate --role=all --save=destdir'
                                ),
                                array(
                                        'descr'   => 'Create core SOAP service in file core.php',
                                        'command' => '--generate --save=core.php'
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
         * The generate service action.
         * @param array $params
         */
        public function generateAction($params = array())
        {
                $this->setOptions($params, 'generate');
                if (!$this->options['role']) {
                        $this->options['with-handle'] = true;
                }
                if ($this->options['role'] == 'all') {
                        $roles = array_keys($this->capabilities->getCapabilities());
                        $destdir = $this->options['save'];
                        foreach ($roles as $role) {
                                $this->options['role'] = $role;
                                $this->options['save'] = sprintf("%s/%s.php", $destdir, sprintf("%sService", ucfirst($role)));
                                $this->perform();
                        }
                } else {
                        $this->perform();
                }
        }

        /**
         * Perform all actions.
         */
        private function perform()
        {
                $generator = new ServiceGenerator(array(), $this->options);
                $service = $generator->getService();
                $service->setNamespace('OpenExam\Library\WebService\Soap\Service');
                $service->addImplements('SoapHandler');
                $service->addUse('OpenExam\Library\WebService\Soap\SoapHandler');

                if ($this->options['role']) {
                        // 
                        // Get resources for this role:
                        // 
                        $resources = $this->capabilities->getResources($this->options['role']);
                        $service->setName(sprintf("%sService", ucfirst($this->options['role'])));
                        $generator->generate($resources);
                } else {
                        // 
                        // Get all capabilities:
                        // 
                        $capabilities = $this->capabilities->getCapabilities();
                        $service->setName("CoreService");

                        // 
                        // Incremental consume all resource access definitions:
                        // 
                        foreach ($capabilities as $role => $resources) {
                                $generator->generate($resources);
                        }
                }

                // 
                // Save to file if requested or print on stdout:
                // 
                if ($this->options['save']) {
                        $service->save($this->options['save']);
                } else {
                        $service->dump();
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
                $this->options = array('verbose' => false, 'force' => false, 'dry-run' => false, 'save');

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'role', 'all', 'core', 'with-handle', 'save');
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
        }

}
