<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/help.php
// Author: Anders Lövgren
// Date:   2009-08-13
// 
// GNU gettext support. 
// 
// The template message file (messages.pot) will be located under the locale
// directory. Create a new translation file using (example for swedish locale):
// 
//   bash$> cd locale && mkdir -p sv_SE/LC_MESSAGES
//   bash$> msginit --no-wrap \
//                  --input=messages.pot \
//                  --output=sv_SE/LC_MESSAGES/sv.po \
//                  --locale=sv_SE
// 
// See 'locale -a' for a list of supported locales. Update and compile the 
// message files issuing 'make' in the root directory. This will update all 
// .po files with new/removed translation strings and compile the .po files 
// to .mo binary files.
// 
// Then add an map between the language code (2-letter) and the locale name
// to the array inside getDefaultMap().
//
// 
// This class supports setting the language and gettext domain. This also
// sets the LC_MESSAGES locale category implicit. It's also possible to set
// other locale categories by calling Locale::setLocale(), i.e. using LC_ALL
// as argument.
// 
// This class ensures that the language (for gettext) is in sync with the 
// locale settings. Note that setting LC_ALL affects the formatting of dates 
// and floatpoint numbers. This could lead to problems if caution is not 
// taken when storing data in the database.
// 
// The lang=c is a special case, that we handles as if we where called without
// cookie or request parameter. It should work as if the page was rendered
// for the first time.
// 
class Locale
{

        private $langpref = null; // The browsers prefered language.
        private $langcode = null; // The two letter country code.
        private $map;             // The language <-> locale map.
        //
        // Construct the gettext and locale object.
        //

        public function __construct()
        {
                $this->map = $this->getDefaultMap();
                if (!$this->detectLanguage()) {
                        reset($this->map);       // Fall back on first language in map
                        $this->langcode = key($this->map);
                }
                $this->setLanguage($this->langcode);
        }

        //
        // Set an alternative locale map.
        //
        public function setLocaleMap($map)
        {
                $this->map = $map;
        }

        //
        // Get the character set for selected locale.
        //
        public function getCharSet()
        {
                return $this->map[$this->langcode]["charset"];
        }

        //
        // Get list of supported languages. Return null if switching
        // language is unsupported (due to missing gettext extension).
        //
        public function getLanguageList()
        {
                if (extension_loaded("gettext")) {
                        return array_keys($this->map);
                }
                return null;
        }

        //
        // Return true if language code is an alias in localemap, i.e.
        // sv => se or en-us => us
        //
        public function isAlias($langcode)
        {
                if (isset($this->map[$langcode])) {
                        return is_string($this->map[$langcode]);
                }
                return false;
        }

        //
        // Get the data (name and locale) associated with the language code.
        //
        public function getLanguageData($langcode)
        {
                if ($this->isAlias($langcode)) {
                        return $this->map[$this->map[$langcode]];
                } else {
                        return $this->map[$langcode];
                }
        }

        //
        // Return true if language is supported.
        //
        public function hasLanguage($langcode)
        {
                if (extension_loaded("gettext")) {
                        return isset($this->map[$langcode]);
                }
                return false;
        }

        //
        // Set language. Return true if successful.
        //
        public function setLanguage($langcode)
        {
                if ($this->langcode != $langcode) {
                        if ($this->hasLanguage($langcode)) {
                                $this->langcode = $langcode;
                        }
                }
                $this->setLocale(LC_MESSAGES);
                $this->setLocale(LC_CTYPE);
                $this->setLocale(LC_TIME);
                $this->setLocale(LC_NUMERIC);
                $this->setTextDomain();
        }

        //
        // Get current selected language.
        //
        public function getLanguage()
        {
                return $this->langcode;
        }

        //
        // Get the browser prefered language, that is, the first language
        // from the accept language list.
        //
        public function getPreferedLanguage()
        {
                return $this->langpref;
        }

        //
        // Get current selected locale.
        //
        public function getLocale()
        {
                return $this->map[$this->langcode]["locale"];
        }

        //
        // Set locale for category according to current selected language.
        // It's not recommended to call this function and later change the
        // language setting by calling Locale::setLanguage(), this might
        // lead to mixed setting for the locale and gettext.
        //
        public function setLocale($category)
        {
                setlocale($category, $this->map[$this->langcode]["locale"]);
        }

        //
        // Get root path of our locale directory.
        //
        public static function getLocaleDir()
        {
                return realpath(dirname(__FILE__) . "/../locale");
        }

        //
        // Set gettext domain based on current locale.
        //
        private function setTextDomain()
        {
                bindtextdomain("openexam", self::getLocaleDir());
                textdomain("openexam");
        }

        //
        // Detect language from browser. If an supported language is found,
        // then it is set as the langcode member and returns true. See RFC
        // 2616 section 14.4 Accept-Language for more information.
        //
        // This function sets $this->langpref to the first language in the
        // accept language list sent by the browser (no matter whether it's
        // supported or not).
        //
        private function detectLanguage()
        {
                if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                        $list = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                        foreach ($list as $str) {
                                list($lang) = explode(";", trim($str));     // lang;q=0.x
                                if (!isset($this->langpref)) {
                                        $this->langpref = substr($lang, 0, 2);
                                }
                                if ($this->hasLanguage($lang)) {
                                        if ($this->isAlias($lang)) {
                                                $lang = $this->map[$lang];
                                        }
                                        $this->langcode = $lang;
                                        return true;
                                }
                        }
                }
                return false;
        }

        //
        // This maps language codes to locale names. Make sure the locale names
        // match these, or the string won't be translated. The C locale is special,
        // we act upon it as if the user hasn't selected any language at all.
        //
        // Make sure to place the value of the lang key inside _() so it gets
        // picked for translation.
        //
        // This setup suffers from the deficiency that it assumes a one-to-one mapping
        // between the country and it's language, and in reality this is not always
        // true, i.e. sv_FI (swedish speaking people in Finland).
        //
        private function getDefaultMap()
        {
                return array
                        (
                        "se" => array(
                                "locale"  => "sv_SE", // Swedish
                                "charset" => "utf-8",
                                "lang"    => _("Swedish")
                        ),
                        "gb"      => array(
                                "locale"  => "en_GB", // English (GB)
                                "charset" => "utf-8",
                                "lang"    => _("English (GB)")
                        ),
                        "us"      => array(
                                "locale"  => "en_US", // English (US)
                                "charset" => "utf-8",
                                "lang"    => _("English (US)")
                        ),
                        "c"       => array(
                                "locale"  => "C", // Browser default
                                "charset" => null,
                                "lang"    => _("Browser default")
                        ),
                        "sv"      => "se",
                        "sv-se"   => "se",
                        "sv-fi"   => "se",
                        "en"      => "gb",
                        "en-gb"   => "gb",
                        "en-us"   => "us"
                );
        }

        //
        // Convert this object to symbolic name.
        //
        public function __toString()
        {
                return $this->map[$this->langcode]["lang"];
        }

}

// 
// Add workaround for missing gettext functions:
// 
if (!extension_loaded('gettext')) {

        function _($text)
        {
                return $text;
        }

        function gettext($text)
        {
                return $text;
        }

        function bindtextdomain($package, $dir)
        {
                
        }

        function textdomain($package)
        {
                
        }

}

// 
// Set language, locale and gettext domain.
//
$locale = new Locale();
if (isset($_GET['lang'])) {
        if ($_GET['lang'] == "c") {
                setcookie("lang", $locale->getLanguage(), 0, "/");
        } else {
                $locale->setLanguage($_GET['lang']);
                setcookie("lang", $_GET['lang'], 0, "/");
        }
} elseif (isset($_COOKIE['lang'])) {
        $locale->setLanguage($_COOKIE['lang']);
}

// 
// Set locale category to current locale.
// 
// $locale->setLocale(LC_ALL);
?>
