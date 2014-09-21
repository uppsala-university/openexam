<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Locale.php
// Created: 2014-09-19 15:52:59
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Locale;

use Phalcon\Mvc\User\Component;

if (!extension_loaded('intl')) {

        /**
         * Drop-in class for missing Locale (from PHP builtin intl extension).
         * 
         * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
         */
        class LocaleFallback
        {

                public static function setDefault($locale)
                {
                        setlocale('LC_ALL', $locale);
                }

                public static function getDefault()
                {
                        return setlocale('LC_ALL', "0");
                }

                public static function getDisplayLanguage($locale)
                {
                        return _($locale);      // translate
                }

        }

}

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
 * $locale->detect('lang', 'en_US');    // 
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Locale extends Component
{

        /**
         * The detected locale.
         * @var \Locale 
         */
        private $locale;
        /**
         * Array of supported locales.
         * @var array 
         */
        private $locales = array();

        /**
         * Constructor.
         * @param string $locale The requested locale (e.g. sv_SE).
         */
        public function __construct($locale = null)
        {
                if (extension_loaded('intl')) {
                        $this->locale = new \Locale();
                        $this->locale->setDefault($locale);
                } else {
                        // TODO: log warning about missing extension.
                        $this->locale = new LocaleFallback();
                        $this->locale->setDefault($locale);
                }
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
                $this->locales = $locales;
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
                return $this->locales;
        }

        /**
         * Add supported local.
         * @param string $locale The supported locale (e.g. sv_SE).
         * @param string $name The display name.
         */
        public function addLocale($locale, $name)
        {
                $this->locales[$locale] = $name;
        }

        /**
         * Set requested locale.
         * @param string $locale The requested locale (e.g. sv_SE).
         */
        public function setLocale($locale)
        {
                $this->locale->setDefault($locale);
        }

        /**
         * Get current locale.
         * @return string
         */
        public function getLocale()
        {
                return $this->locale->getDefault();
        }

        /**
         * Check if given locale is supported.
         * @param string $locale The requested locale (e.g. sv_SE).
         * @return boolean
         */
        public function hasLocale($locale)
        {
                if (!isset($this->locales)) {
                        return false;
                } else {
                        return array_key_exists($locale, $this->locales);
                }
        }

        /**
         * Get display language for given locale.
         * @param string $locale The locale name.
         */
        public function getDisplayLanguage($locale)
        {
                return $this->locale->getDisplayLanguage($locale);
        }

        /**
         * Detect prefered locale.
         * @param string $name The request parameter name.
         * @param string $default The default locale.
         */
        public function detect($name, $default)
        {
                $locale = null;

                if (php_sapi_name() != "cli") {
                        if ($this->request->has($name)) {
                                $locale = $this->request->get($name, "string");
                        } elseif ($this->session->has($name)) {
                                $locale = $this->session->get($name);
                        } elseif ($this->persistent->has($name)) {
                                $locale = $this->persistent->get($name);
                        } elseif ($this->request->getBestLanguage()) {
                                $locale = $this->request->getBestLanguage();
                        } else {
                                $locale = 'C';
                        }
                } else {
                        foreach (array('LC_CTYPE', 'LANG') as $name) {
                                if (filter_input(INPUT_ENV, $name, FILTER_SANITIZE_STRING)) {
                                        $locale = filter_input(INPUT_ENV, $name, FILTER_SANITIZE_STRING);
                                }
                        }
                }

                if (isset($locale)) {
                        $this->locale->setDefault($locale);
                        $this->session->set($name, $locale);
                } else {
                        $this->locale->setDefault($default);
                        $this->session->set($name, $default);
                }
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
                $iterator = new \DirectoryIterator($dir);
                foreach ($iterator as $dir) {
                        $locale = $dir->getBasename();
                        $lang = $this->locale->getDisplayLanguage($locale);
                        $locales[$locale] = $lang;
                }
                return $locales;
        }

}
