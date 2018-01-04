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
// File:    Initialize.php
// Created: 2014-09-19 14:03:16
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext\Command;

use OpenExam\Library\Globalization\Exception;

/**
 * Initialize command.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Initialize extends Command
{

        /**
         * Initialize text domain template and PO-file for text domain.
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

                // 
                // Create locale directory:
                // 
                $this->createLocaleDirectory($locdir);

                // 
                // Create template if missing:
                // 
                $template = $this->createTemplateFile($topdir, $module);
                
                if(!file_exists($template)) {
                        $this->_flash->warning("Failed create $template");
                        return;
                }

                // 
                // Creates new PO file:
                // 
                $this->createMessageCatalog($locdir, $template, $locale, $module);
        }

        private function createLocaleDirectory($locdir)
        {
                if (!file_exists($locdir)) {
                        if ($this->_options['verbose']) {
                                $this->_flash->notice("Creating directory $locdir");
                        }
                        if (!$this->_options['dry-run']) {
                                if (!mkdir($locdir, 0755, true)) {
                                        throw new Exception("Failed create directory $locdir");
                                }
                        }
                }
        }

        private function createMessageCatalog($locdir, $template, $locale, $module)
        {
                $pofile = sprintf("%s/%s.po", $locdir, $module);
                if (!file_exists($pofile)) {
                        if ($this->_options['verbose']) {
                                $this->_flash->notice("Creating PO-file $pofile");
                        }
                        $program = $this->_config->gettext->program->msginit;
                        $options = $this->_config->gettext->options->msginit;

                        $cmdopts = $this->substitute($options, array('template' => $template, 'output' => $pofile, 'locale' => $locale));
                        $this->execute($program, $cmdopts);
                } else {
                        $this->_flash->warning("Cowardly refused to overwrite existing $pofile");
                }
        }

        private function createTemplateFile($topdir, $module)
        {
                $template = sprintf("%s/%s.pot", $topdir, $module);

                if (!file_exists($template)) {
                        if ($this->_options['verbose']) {
                                $this->_flash->notice("Creating template $template");
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
                return $template;
        }

}
