<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Clean.php
// Created: 2014-09-19 14:19:11
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext\Command;

/**
 * Cleanup command.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Clean extends Command
{

        /**
         * Cleanup (delete) MO-files.
         */
        public function process()
        {
                foreach ($this->getLocales() as $locale) {
                        $this->processLocale($locale);
                }
        }

        private function processLocale($locale)
        {
                foreach ($this->getModules() as $module) {
                        $this->processModule($locale, $module);
                }
        }

        private function processModule($locale, $module)
        {
                $file = sprintf("%s/%s/%s.mo", $this->_config->application->localeDir, $locale, $module);
                if (file_exists($file)) {
                        $this->processFile($file);
                }
        }

        private function processFile($file)
        {
                if ($this->_options['verbose']) {
                        $this->_flash->notice("Deleting $file");
                }
                if (!$this->_options['dry-run']) {
                        if (!unlink($file)) {
                                $this->_flash->error("Failed unlink $file");
                        }
                }
        }

}
