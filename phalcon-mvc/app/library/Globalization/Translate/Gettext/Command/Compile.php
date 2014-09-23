<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Compile.php
// Created: 2014-09-19 14:18:18
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext\Command;

use OpenExam\Library\Globalization\Exception;

/**
 * Compile command.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Compile extends Command
{

        /**
         * Compile PO-file to MO-file.
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
                $mmerge = array();

                $topdir = $this->config->application->localeDir;
                $locdir = sprintf("%s/%s/LC_MESSAGES", $topdir, $locale);

                $mofile = sprintf("%s/%s.mo", $locdir, $module);
                $pofile = sprintf("%s/%s.po", $locdir, $module);

                if ($this->hasMergeModules($module)) {
                        $mmerge = $this->getMergeModules($module, $locale);
                        $pofile = $this->getConcatFile($pofile, $mmerge);
                }

                if (!file_exists($pofile)) {
                        throw new Exception("Missing PO-file $pofile");
                } else {
                        $this->processFile($pofile, $mofile, $mmerge);
                }
        }

        private function processFile($pofile, $mofile)
        {
                if ($this->options['verbose']) {
                        $this->flash->notice("Compiling PO-file $pofile");
                }
                $program = $this->config->gettext->program->msgfmt;
                $options = $this->config->gettext->options->msgfmt;

                if ($this->options['verbose']) {
                        $options .= " --verbose";
                }

                $cmdopts = $this->substitute($options, array('output' => $mofile));
                $cmdopts = sprintf("%s %s", $cmdopts, $pofile);

                $this->execute($program, $cmdopts);
        }

        private function hasMergeModules($module)
        {
                return isset($this->config->translate->$module->merge);
        }

        private function getMergeModules($module, $locale)
        {
                $topdir = $this->config->application->localeDir;
                $locdir = sprintf("%s/%s/LC_MESSAGES", $topdir, $locale);
                $mmerge = array();

                foreach ($this->config->translate->$module->merge as $merge) {
                        $mmerge[] = sprintf("%s/%s.po", $locdir, $merge);
                }

                return $mmerge;
        }

        private function getConcatFile($pofile, $merge)
        {
                if ($this->options['verbose']) {
                        $this->flash->notice("Concatenating PO-files");
                }
                $program = $this->config->gettext->program->msgcat;
                $options = $this->config->gettext->options->msgcat;

                if ($this->options['verbose']) {
                        $options .= " --verbose";
                }

                $catfile = sprintf("%s.cat", $pofile);

                $cmdopts = $this->substitute($options, array('output' => $catfile));
                $cmdopts = sprintf("%s %s %s", $cmdopts, $pofile, implode(" ", $merge));

                $this->execute($program, $cmdopts);
                return $catfile;
        }

}
