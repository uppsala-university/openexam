<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Merge.php
// Created: 2014-09-19 14:18:49
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext\Command;

use OpenExam\Library\Globalization\Exception;

/**
 * Merge command.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Merge extends Command
{

        /**
         * Merge message catalog (PO-files) with template (POT-file).
         * @throws Exception
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
                $topdir = $this->config->application->localeDir;
                $locdir = sprintf("%s/%s/LC_MESSAGES", $topdir, $locale);

                $template = sprintf("%s/%s.pot", $topdir, $module);
                $pofile = sprintf("%s/%s.po", $locdir, $module);

                if (!file_exists($template)) {
                        throw new Exception("Missing template file $template");
                }
                if (!file_exists($pofile)) {
                        throw new Exception("Missing PO-file $pofile");
                }

                $this->processFile($pofile, $template);
        }

        private function processFile($pofile, $template)
        {
                if ($this->options['verbose']) {
                        $this->flash->notice("Merging PO-file $pofile");
                }
                $program = $this->config->gettext->program->msgmerge;
                $options = $this->config->gettext->options->msgmerge;

                if ($this->options['verbose']) {
                        $options .= " --verbose";
                }

                $cmdopts = $this->substitute($options);
                $cmdopts = sprintf("%s %s %s", $cmdopts, $pofile, $template);

                $this->execute($program, $cmdopts);
        }

}
