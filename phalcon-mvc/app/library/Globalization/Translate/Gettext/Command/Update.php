<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Update.php
// Created: 2014-09-19 14:17:49
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext\Command;

/**
 * Update command.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Update extends Command
{

        /**
         * Update text domain template file (POT-file).
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
                $template = sprintf("%s/%s.pot", $topdir, $module);

                $this->processFile($template, $module, $topdir);
        }

        private function processFile($template, $module, $topdir)
        {
                if ($this->options['verbose']) {
                        $this->flash->notice("Updating template $template");
                }

                $program = $this->config->gettext->program->xgettext;
                $options = $this->config->gettext->options->xgettext;

                $cmdopts = $this->substitute($options, array('template' => $template));

                $filelist = sprintf("%s/%s.list", $topdir, $module);
                file_put_contents($filelist, implode("\n", $this->getSourceFiles()));

                $cmdopts = sprintf("%s --files-from=%s", $cmdopts, $filelist);
                $this->execute($program, $cmdopts);

                unlink($filelist);
        }

}
