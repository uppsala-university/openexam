<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Pattern.php
// Created: 2014-12-15 03:39:34
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core;

use Phalcon\Mvc\User\Component;

/**
 * Regex pattern validation.
 * 
 * The default regex patterns are named REGEX_XXX. Use MATCH_NAME as name
 * arguments for get(), set() and match(). 
 * 
 * The defaults can be overridden in the system config. For example, this 
 * example will replace the default username pattern (enforcing lower case
 * characters and digits) with a pattern enforcing 8 characters usernames
 * using any letter:
 * 
 * <code>
 * $config = array(
 *              ...
 *      'patterns' => array(
 *                'user' => "/^([\w]{8})@?([\w-_\.]{1,45})?$/"
 *       ),
 *      ...
 * )
 * </code>
 * 
 * The same can be accomplished programatically:
 * <code>
 * Pattern::set(Pattern::MATCH_USER, "/^([\w]{8})@?([\w-_\.]{1,45})?$/");
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 * @see http://www.regular-expressions.info/unicode.html
 */
class Pattern extends Component
{

        // 
        // Generic:
        // 
        /**
         * Match anything.
         */
        const MATCH_ANY = 'any';
        const REGEX_ANY = "/^.*$/";
        /**
         * Match an empty string.
         */
        const MATCH_NOTHING = 'nothing';
        const REGEX_NOTHING = "/^$/";
        /**
         * Match URL (i.e. http://server/file).
         */
        const MATCH_URL = 'url';
        const REGEX_URL = "/^((https?|ftps?|ssh|sftp):\/\/.*|)$/";
        // 
        // Number:
        // 
        /**
         * Float point number (locale independent).
         */
        const MATCH_FLOAT = 'float';
        const REGEX_FLOAT = "/^((\d)*?([,.]{0,1})(\d+))$/";
        /**
         * Database index.
         */
        const MATCH_INDEX = 'index';
        const REGEX_INDEX = "/^-?\d+$/";
        /**
         * Answer score (e.g. 2,5 p).
         */
        const MATCH_SCORE = 'score';
        const REGEX_SCORE = "/^(\d*?[,.]{0,1}\d+)\s*(p.*|)$/i";
        // 
        // Text:
        // 
        /**
         * Multi line text.
         */
        const MATCH_MULTI_LINE_TEXT = 'mutli-line-text';
        const REGEX_MULTI_LINE_TEXT = "/^(\p{L}|\p{N}|\p{Z}|\p{P}|)+$/um";
        /**
         * Single line text.
         */
        const MATCH_SINGLE_LINE_TEXT = 'single-line-text';
        const REGEX_SINGLE_LINE_TEXT = "/^(\p{L}|\p{N}|\p{Z}|\p{P}|)+$/u";
        // 
        // User:
        // 
        /**
         * User or principal name.
         */
        const MATCH_USER = 'user';
        const REGEX_USER = "/^([[:lower:][:digit:]-_]{1,15})@?([\w-_\.]{1,45})?$/";
        /**
         * Match anonymous code.
         */
        const MATCH_CODE = 'code';
        const REGEX_CODE = "/^([0-9a-zA-Z\-_]{1,15}|)$/";
        /**
         * Personal name (unicode).
         */
        const MATCH_NAME = 'name';
        const REGEX_NAME = "/^(\p{L}|\p{N}|\p{Z}|\p{P}|)+$/u";
        /**
         * Personal number (including foreign with leading or trailing letter).
         */
        const MATCH_PERSNR = 'persnr';
        const REGEX_PERSNR = "/^(\d{6,8})-?(\d{4}|[a-zA-Z]\d{3}|\d{3}[a-zA-Z])$/";
        // 
        // Course:
        // 
        /**
         * Match course code.
         */
        const MATCH_COURSE = 'course';
        const REGEX_COURSE = "/^[0-9a-zA-Z \-]{1,20}$/";
        /**
         * Match year (YYYY or YY).
         */
        const MATCH_YEAR = 'year';
        const REGEX_YEAR = "/^((19|20)?[0-9]{2})$/";
        /**
         * Match semester (termin).
         */
        const MATCH_TERMIN = 'termin';
        const REGEX_TERMIN = "/^[1-2]$/";

        /**
         * All registered patterns.
         * @var array 
         */
        private $_patterns = array(
                self::MATCH_ANY              => self::REGEX_ANY,
                self::MATCH_CODE             => self::REGEX_CODE,
                self::MATCH_COURSE           => self::REGEX_COURSE,
                self::MATCH_FLOAT            => self::REGEX_FLOAT,
                self::MATCH_INDEX            => self::REGEX_INDEX,
                self::MATCH_MULTI_LINE_TEXT  => self::REGEX_MULTI_LINE_TEXT,
                self::MATCH_NAME             => self::REGEX_NAME,
                self::MATCH_NOTHING          => self::REGEX_NOTHING,
                self::MATCH_PERSNR           => self::REGEX_PERSNR,
                self::MATCH_SCORE            => self::REGEX_SCORE,
                self::MATCH_SINGLE_LINE_TEXT => self::REGEX_SINGLE_LINE_TEXT,
                self::MATCH_TERMIN           => self::REGEX_TERMIN,
                self::MATCH_URL              => self::REGEX_URL,
                self::MATCH_USER             => self::REGEX_USER,
                self::MATCH_YEAR             => self::REGEX_YEAR
        );
        /**
         * The singleton instance.
         * @var Pattern 
         */
        private static $_instance;

        /**
         * Constructor.
         */
        private function __construct()
        {
                if (isset($this->config->patterns)) {
                        foreach ($this->config->patterns as $name => $str) {
                                $this->_patterns[$name] = $str;
                        }
                }
        }

        /**
         * Get singleton object.
         * @return Pattern
         */
        private static function instance()
        {
                if (!isset(self::$_instance)) {
                        self::$_instance = new Pattern();
                }
                return self::$_instance;
        }

        /**
         * Set regex pattern.
         * 
         * @param string $name The pattern name (i.e. year).
         * @param string $str The regex pattern (i.e. /^((19|20)?[0-9]{2})$/)
         */
        public static function set($name, $str)
        {
                self::instance()->_patterns[$name] = $str;
        }

        /**
         * Get regex pattern.
         * 
         * @param string $name The pattern name (i.e. year).
         * @return string
         */
        public static function get($name)
        {
                return self::instance()->_patterns[$name];
        }

        /**
         * Match string against regex pattern.
         * 
         * @param string $name The pattern name (i.e. year).
         * @param string $str The regex pattern (i.e. /^((19|20)?[0-9]{2})$/)
         * @return boolean
         */
        public static function match($name, $str, &$matches = null)
        {
                return (bool) preg_match(self::instance()->_patterns[$name], $str, $matches);
        }

}
