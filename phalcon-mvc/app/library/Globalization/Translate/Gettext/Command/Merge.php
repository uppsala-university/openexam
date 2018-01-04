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
                $topdir = $this->_config->application->localeDir;
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
                if ($this->_options['verbose']) {
                        $this->_flash->notice("Merging PO-file $pofile");
                }
                $program = $this->_config->gettext->program->msgmerge;
                $options = $this->_config->gettext->options->msgmerge;

                if ($this->_options['verbose']) {
                        $options .= " --verbose";
                }

                $cmdopts = $this->substitute($options);
                $cmdopts = sprintf("%s %s %s", $cmdopts, $pofile, $template);

                $this->execute($program, $cmdopts);
        }

}
