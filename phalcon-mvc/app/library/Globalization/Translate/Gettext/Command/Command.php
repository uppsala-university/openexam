<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Command.php
// Created: 2014-09-19 13:47:09
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext\Command;

use FilesystemIterator;
use FilterIterator;
use OpenExam\Library\Globalization\Exception;
use Phalcon\Config;
use Phalcon\DI\Injectable;
use Phalcon\Flash;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Source code filter.
 * @internal
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SourceFileFilter extends FilterIterator
{

        /**
         * Accepted file extensions.
         * @var array
         */
        private $_filter;

        /**
         * Constructor.
         * @param Iterator $iterator
         * @param array $filter The accepted file extensions.
         */
        public function __construct($iterator, $filter)
        {
                parent::__construct($iterator);
                $this->_filter = $filter;
        }

        public function accept()
        {
                $file = $this->current();
                return in_array($file->getExtension(), $this->_filter, true);
        }

}

/**
 * Abstract base class for commands.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class Command
{

        /**
         * Runtime options.
         * @var array 
         */
        protected $_options;
        /**
         * @var Flash 
         */
        protected $_flash;
        /**
         * @var Config
         */
        protected $_config;
        /**
         * The text domain.
         * @var string 
         */
        protected $_domain;
        /**
         * The locale name.
         * @var string 
         */
        protected $_locale;

        /**
         * Constructor.
         * @param Injectable $setup The command consumer.
         * @param array $options Runtime options.
         */
        public function __construct($setup, $options = array())
        {
                $this->_flash = $setup->flash;
                $this->_config = $setup->config;
                $this->_options = $options;
                $this->_domain = $options['module'];
                $this->_locale = $options['locale'];
        }

        /**
         * Execute an command using options.
         * 
         * @param string $program The program name.
         * @param string $options The program options.
         * @throws Exception
         */
        protected function execute($program, $options)
        {
                $command = sprintf("%s %s", $program, $options);

                if ($this->_options['verbose'] || $this->_options['dry-run']) {
                        $this->_flash->notice($command);
                }
                if (!$this->_options['dry-run']) {
                        $descr = array(
                                0 => array("pipe", "r"),
                                1 => array("pipe", "w"),
                                2 => array("pipe", "w")
                        );

                        $process = proc_open($command, $descr, $pipes);
                        if (!is_resource($process)) {
                                throw new Exception(sprintf("Failed run %s", basename($program)));
                        }

                        $stdout = stream_get_contents($pipes[1]);
                        $stderr = stream_get_contents($pipes[2]);
                        $status = proc_get_status($process);

                        fclose($pipes[0]);
                        fclose($pipes[1]);
                        fclose($pipes[2]);

                        $result = proc_close($process);
                        $exited = $status['running'] ? $result : $status['exitcode'];

                        if ($exited != 0) {
                                $this->_flash->error(sprintf("Failed run %s", basename($program)));
                                throw new Exception($stderr);
                        } elseif ($this->_options['verbose']) {
                                if (strlen($stdout) != 0) {
                                        $this->_flash->success($stdout);
                                }
                                if (strlen($stderr) != 0) {
                                        $this->_flash->success($stderr);
                                }
                        }
                }
        }

        /**
         * Substitute command options.
         * 
         * @param array $options Generic command options.
         * @param array $extra Command specific options.
         * @return string
         */
        protected function substitute($options, $extra = array())
        {
                $search = array();
                $replace = array();

                $subst = array_merge($this->_config->gettext->package->toArray(), $search, $extra);

                foreach ($subst as $key => $val) {
                        $search[] = "@$key@";
                        $replace[] = $val;
                }

                return str_replace($search, $replace, $options);
        }

        /**
         * Recursive find files in subdirectories.
         * @param array $directories An array of sub directories.
         * @param array $filter File extension filter.
         * @return SourceFileFilter
         */
        protected function findFiles($directories = array('.'), $filter = array('php', 'phtml'))
        {
                $files = array();

                foreach ($directories as $directory) {
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator(
                            $directory, FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::SKIP_DOTS
                            ), RecursiveIteratorIterator::CHILD_FIRST);

                        $filtered = new SourceFileFilter($iterator, $filter);
                        foreach ($filtered as $file) {
                                $files[] = $file;
                        }
                }

                return $files;
        }

        /**
         * Get source code files.
         * @return array
         */
        protected function getSourceFiles()
        {
                $files = array();
                $input = $this->_config->translate->get($this->_options['module'])->toArray();

                if (isset($input['directories'])) {
                        $files = $this->findFiles($input['directories']);
                }
                if (isset($input['files'])) {
                        $files = array_merge($files, $input['files']);
                }

                return $files;
        }

        /**
         * Get list of locales.
         * @return array
         */
        protected function getLocales()
        {
                $locales = array();

                if ($this->_locale) {
                        $locales[] = $this->_locale;
                } else {
                        $iterator = new \DirectoryIterator($this->_config->application->localeDir);
                        foreach ($iterator as $file) {
                                if (preg_match("/[a-z]{2}_[A-Z]{2}/", $file)) {
                                        $locales[] = basename($file);
                                }
                        }
                }
                return $locales;
        }

        /**
         * Get list of modules.
         * @return array
         */
        protected function getModules()
        {
                $modules = array();

                if ($this->_domain) {
                        $modules[] = $this->_domain;
                } else {
                        foreach (array_keys($this->_config->translate) as $module) {
                                $modules[] = $module;
                        }
                }

                return $modules;
        }

        /**
         * Run command.
         */
        abstract function process();
}
