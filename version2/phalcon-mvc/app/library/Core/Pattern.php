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

/**
 * Regex pattern for input validation and substitution.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 * @see http://www.regular-expressions.info/unicode.html
 */
class Pattern
{

        // 
        // Generic:
        // 
        /**
         * Match anything.
         */
        const ANY = "/^.*$/";
        /**
         * Match an empty string.
         */
        const NOTHING = "/^$/";
        /**
         * Match URL (i.e. http://server/file).
         */
        const URL = "/^((https?|ftps?|ssh|sftp):\/\/.*|)$/";
        // 
        // Number:
        // 
        /**
         * Float point number (locale independent).
         */
        const FLOAT = "/^((\d)*?([,.]{0,1})(\d+))$/";
        /**
         * Database index.
         */
        const INDEX = "/^-?\d+$/";
        /**
         * Answer score (e.g. 2,5 p).
         */
        const SCORE = "/^(\d*?[,.]{0,1}\d+)\s*(p.*|)$/i";
        // 
        // Text:
        // 
        /**
         * Multi line text.
         */
        const MULTI_LINE_TEXT = "/^(\p{L}|\p{N}|\p{Z}|\p{P}|)+$/um";
        /**
         * Single line text.
         */
        const SINGLE_LINE_TEXT = "/^(\p{L}|\p{N}|\p{Z}|\p{P}|)+$/u";
        // 
        // User:
        // 
        /**
         * User or principal name.
         */
        const USER = "/^([\w-_]{1,20})@?([\w-_\.]{1,40})?$/";
        /**
         * Match anonymous code.
         */
        const CODE = "/^([0-9a-zA-Z\-_]{1,15}|)$/";
        /**
         * Personal name (unicode).
         */
        const NAME = "/^(\p{L}|\p{N}|\p{Z}|\p{P}|)+$/u";
        /**
         * Personal number (including foreign with leading or trailing letter).
         */
        const PERSNR = "/^(\d{6,8})-?(\d{4}|[a-zA-Z]\d{3}|\d{3}[a-zA-Z])$/";
        // 
        // Course:
        // 
        /**
         * Match course code.
         */
        const COURSE = "/^[0-9a-zA-Z \-]{1,20}$/";
        /**
         * Match year (YYYY or YY).
         */
        const YEAR = "/^((19|20)?[0-9]{2})$/";
        /**
         * Match semester (termin).
         */
        const TERMIN = "/^[1-2]$/";

}
