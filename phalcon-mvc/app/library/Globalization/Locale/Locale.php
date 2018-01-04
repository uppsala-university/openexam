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
// File:    Locale.php
// Created: 2014-09-19 15:52:59
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Locale;

use DirectoryIterator;
use Locale as SystemLocale;
use Phalcon\Mvc\User\Component;

/**
 * Localization handling class (L10N).
 * 
 * Calling detect() makes this class automatic detect the prefered locale
 * from a number of different source:
 * 
 * <ol>
 * <li>Constructor argument.</li>
 * <li>Request parameter.</li>
 * <li>Session cookie.</li>
 * <li>Persistent storage.</li>
 * <li>Browser prefered language.</li>
 * <li>CLI Environment (LC_CTYPE and LANG)</li>
 * </ol>
 * 
 * The detected locale is saved in the session under the same name as the
 * request parameter:
 * 
 * <code>
 * $locale = new Locale();
 * $locale->addLocale('sv_SE', 'Swedish');
 * // ...
 * $locale->detect('locale', 'en_US');    // 
 * </code>
 * 
 * This class affects the behavior of other functions (i.e. setlocale() and 
 * strftime()) that depends on current system locale. To force locale detection 
 * by the locale service, call $this->locale->detect(null) at least once.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Locale extends Component
{

        /**
         * Array of supported locales.
         * @var array 
         */
        private $_locales = array();
        /**
         * Interface between web server and PHP.
         * @var type 
         */
        protected $_sapi;

        /**
         * Constructor.
         * @param string $locale The requested locale (e.g. sv_SE).
         */
        public function __construct($locale = null)
        {
                $this->_sapi = php_sapi_name();
                $this->setLocale($locale);
        }

        /**
         * Add array of supported locales.
         * 
         * The $locales argument is an array were the keys are the locale
         * and the value is the language string for that locale.
         * 
         * @param array $locales
         */
        public function setLocales($locales)
        {
                $this->_locales = $locales;
        }

        /**
         * Get all supported locales.
         * 
         * The keys are the locale and the values are the language string
         * for that locale.
         * 
         * @return array
         */
        public function getLocales()
        {
                return $this->_locales;
        }

        /**
         * Add supported local.
         * @param string $locale The supported locale (e.g. sv_SE).
         * @param string $name The display name.
         */
        public function addLocale($locale, $name)
        {
                $this->_locales[$locale] = $name;
        }

        /**
         * Set requested locale.
         * 
         * @param string $locale The requested locale (e.g. sv_SE).
         * @param string $name Store locale in named session variable.
         * @return bool 
         */
        public function setLocale($locale, $name = 'locale')
        {
                if (!isset($locale)) {
                        return false;
                } else {
                        $default = $this->getDefault();
                }

                if (!$this->setDefault($locale)) {
                        return false;
                }
                if (!setlocale(LC_ALL, $locale)) {
                        $this->setDefault($default);    // restore
                        return false;
                }
                if ($this->session->isStarted()) {
                        $this->session->set($name, $locale);
                }

                return true;
        }

        private function getDefault()
        {
                if (extension_loaded('intl')) {
                        return SystemLocale::getDefault();
                }
        }

        private function setDefault($locale)
        {
                if (extension_loaded('intl')) {
                        return SystemLocale::setDefault($locale);
                } else {
                        return true;
                }
        }

        /**
         * Get current locale.
         * @return string
         */
        public function getLocale()
        {
                return setlocale(LC_CTYPE, "0");
        }

        /**
         * Check if given locale is supported.
         * @param string $locale The requested locale (e.g. sv_SE).
         * @return boolean
         */
        public function hasLocale($locale)
        {
                if (!isset($this->_locales)) {
                        return false;
                } else {
                        return array_key_exists($locale, $this->_locales);
                }
        }

        /**
         * Get display language for given locale.
         * @param string $locale The locale name.
         * @return string 
         */
        public function getDisplayLanguage($locale)
        {
                if (extension_loaded('intl')) {
                        return SystemLocale::getDisplayLanguage($locale);
                } elseif (extension_loaded('gettext')) {
                        return $this->tr->_($locale);
                } else {
                        return $locale;
                }
        }

        /**
         * Get language code. For example, if locale is 'en_GB', then the
         * language code is 'en'.
         * 
         * @param string $locale The locale name.
         * @return string
         */
        public function getRegion($locale)
        {
                if (extension_loaded('intl')) {
                        return SystemLocale::getRegion($locale);
                } else {
                        return substr($locale, 3, 2);
                }
        }

        /**
         * Get language region. For example, if locale is 'en_GB', then region
         * is 'GB' while language is 'en'.
         * 
         * @param string $locale The locale name.
         * @return string
         */
        public function getLanguage($locale)
        {
                if (extension_loaded('intl')) {
                        return SystemLocale::getPrimaryLanguage($locale);
                } else {
                        return substr($locale, 0, 2);
                }
        }

        /**
         * Detect preferred locale.
         * 
         * @param string $name The request parameter name.
         * @param string $default The default locale.
         * @param bool $apply Set locale to detected or default.
         * @return string The detected locale or $default.
         */
        public function detect($name = 'locale', $default = 'C', $apply = false)
        {
                if (is_null($name)) {
                        return $this->getLocale();
                }

                $locale = null;

                if ($this->_sapi != "cli") {
                        if ($this->request->has($name)) {
                                $locale = $this->request->get($name, "string");
                        } elseif ($this->session->has($name)) {
                                $locale = $this->session->get($name);
                        } elseif ($this->persistent->has($name)) {
                                $locale = $this->persistent->get($name);
                        } elseif ($this->request->getBestLanguage()) {
                                $locale = $this->request->getBestLanguage();
                        }
                } else {
                        foreach (array('LC_CTYPE', 'LANG') as $name) {
                                if (filter_input(INPUT_ENV, $name, FILTER_SANITIZE_STRING)) {
                                        $locale = filter_input(INPUT_ENV, $name, FILTER_SANITIZE_STRING);
                                } elseif (strlen(getenv($name)) > 0) {
                                        $locale = getenv($name);
                                } elseif (isset($_ENV[$name])) {
                                        $locale = $_ENV[$name];
                                }
                        }
                }

                if (!array_key_exists($locale, $this->_locales)) {
                        if ($this->findLocale($locale) ||
                            $this->findVariant($locale) ||
                            $this->findAlias($locale)) {
                                
                        } else {
                                $locale = $default;
                        }
                }

                if ($apply) {
                        $this->setLocale($locale, $name);
                }

                return $locale;
        }

        /**
         * Get all supported locales.
         * 
         * The keys are the locale and the values are the language string for
         * that locale. All available locales are enumerated from the supplied
         * directory.
         * 
         * @param string $langdir The language directory.
         * @return array
         */
        public function findLocales($langdir)
        {
                $locales = array();
                $iterator = new DirectoryIterator($langdir);
                foreach ($iterator as $dir) {
                        $locale = $dir->getBasename();
                        if (extension_loaded('intl')) {
                                $lang = SystemLocale::getDisplayLanguage($locale);
                        } else {
                                $lang = $locale;
                        }
                        $locales[$locale] = $lang;
                }
                return $locales;
        }

        /**
         * Find locale by language string.
         * 
         * This function matches the language code against the language 
         * part (the first two characters) in all set locales. The $locale 
         * argument is set to matching locale if found.
         * 
         * @param string $locale The language string (e.g. en).
         * @return boolean
         */
        private function findLocale(&$locale)
        {
                if (strlen($locale) == 2) {
                        foreach (array_keys($this->_locales) as $key) {
                                $match = substr($key, 0, 2);
                                if ($locale == $match) {
                                        $locale = $key;
                                        return true;
                                }
                        }
                }
                return false;
        }

        /**
         * Find locale by language string.
         * 
         * This function matches the language code against the variant in
         * all set locales. The $locale argument is set to matching locale 
         * if found.
         * 
         * @param type $locale
         * @return boolean
         */
        private function findVariant(&$locale)
        {
                if (strlen($locale) == 2) {
                        foreach (array_keys($this->_locales) as $key) {
                                $match = strtolower(substr($key, 3, 2));
                                if ($locale == $match) {
                                        $locale = $key;
                                        return true;
                                }
                        }
                }
                return false;
        }

        /**
         * Find locale by language string.
         * 
         * This function matches the language code against aliases in all
         * set locales (e.g. en-us => en_US). The $locale argument is set to 
         * matching locale if found.
         * 
         * @param type $locale
         * @return boolean
         */
        private function findAlias(&$locale)
        {
                if (strlen($locale) > 3 && $locale[2] == '-') {
                        foreach (array_keys($this->_locales) as $key) {
                                $match = str_replace('_', '-', $key);
                                if (strncasecmp($locale, $match, strlen($locale)) == 0) {
                                        $locale = $key;
                                        return true;
                                }
                        }
                }
                return false;
        }

}
