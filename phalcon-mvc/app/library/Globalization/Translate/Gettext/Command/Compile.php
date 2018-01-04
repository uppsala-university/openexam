<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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

                $topdir = $this->_config->application->localeDir;
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
                if ($this->_options['verbose']) {
                        $this->_flash->notice("Compiling PO-file $pofile");
                }
                $program = $this->_config->gettext->program->msgfmt;
                $options = $this->_config->gettext->options->msgfmt;

                if ($this->_options['verbose']) {
                        $options .= " --verbose";
                }

                $cmdopts = $this->substitute($options, array('output' => $mofile));
                $cmdopts = sprintf("%s %s", $cmdopts, $pofile);

                $this->execute($program, $cmdopts);
        }

        private function hasMergeModules($module)
        {
                return isset($this->_config->translate->$module->merge);
        }

        private function getMergeModules($module, $locale)
        {
                $topdir = $this->_config->application->localeDir;
                $locdir = sprintf("%s/%s/LC_MESSAGES", $topdir, $locale);
                $mmerge = array();

                foreach ($this->_config->translate->$module->merge as $merge) {
                        $mmerge[] = sprintf("%s/%s.po", $locdir, $merge);
                }

                return $mmerge;
        }

        private function getConcatFile($pofile, $merge)
        {
                if ($this->_options['verbose']) {
                        $this->_flash->notice("Concatenating PO-files");
                }
                $program = $this->_config->gettext->program->msgcat;
                $options = $this->_config->gettext->options->msgcat;

                if ($this->_options['verbose']) {
                        $options .= " --verbose";
                }

                $catfile = sprintf("%s.cat", $pofile);

                $cmdopts = $this->substitute($options, array('output' => $catfile));
                $cmdopts = sprintf("%s %s %s", $cmdopts, $pofile, implode(" ", $merge));

                $this->execute($program, $cmdopts);
                return $catfile;
        }

}
