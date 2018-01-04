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
                $topdir = $this->_config->application->localeDir;
                $template = sprintf("%s/%s.pot", $topdir, $module);

                $this->processFile($template, $module, $topdir);
        }

        private function processFile($template, $module, $topdir)
        {
                if ($this->_options['verbose']) {
                        $this->_flash->notice("Updating template $template");
                }

                $program = $this->_config->gettext->program->xgettext;
                $options = $this->_config->gettext->options->xgettext;

                $cmdopts = $this->substitute($options, array('template' => $template));

                $filelist = sprintf("%s/%s.list", $topdir, $module);
                file_put_contents($filelist, implode("\n", $this->getSourceFiles()));

                $cmdopts = sprintf("%s --files-from=%s", $cmdopts, $filelist);
                $this->execute($program, $cmdopts);

                unlink($filelist);
        }

}
