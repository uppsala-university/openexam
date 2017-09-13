<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Size.php
// Created: 2017-09-13 14:54:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core;

/**
 * Size limit calculator.
 * 
 * This class helps work with PHP setting values (like post_max_size). Pass 
 * either an integer, array or string for constructor:
 * 
 * <code>
 * $size = new Size(ini_get('post_max_size');
 * $size = new Size(8388608);   // 8Mb
 * $size = new Size('8M');      // 8Mb
 * $size = new Size(array(
 *      'size'   => 8, 
 *      'suffix' => 'M'
 * ));
 * </code>
 * 
 * @property-read int $size The calculated size.
 * @property-read string $suffix The size suffix.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Size
{

        /**
         * The size in bytes.
         * @var int 
         */
        private $_size;
        /**
         * The shorthand suffix.
         * @var string 
         */
        private $_suffix;
        /**
         * Suffix to size map.
         * @var array 
         */
        private static $_sizes = array(
                'B' => 1,
                'K' => 1024,
                'M' => 1048576,
                'G' => 1073741824
        );

        /**
         * Constructor.
         * @param int|string|array|Size $value The size value.
         */
        public function __construct($value)
        {
                if ($value instanceof Size) {
                        $this->_size = $value->size;
                        $this->_suffix = $value->_suffix;
                } elseif (is_int($value)) {
                        $this->_size = $value;
                        $this->_suffix = null;
                } elseif (is_array($value)) {
                        $this->_size = $value['size'];
                        $this->_suffix = $value['suffix'];
                } elseif (is_string($value)) {
                        $this->_size = substr($value, 0, -1);
                        $this->_suffix = substr($value, -1);
                } else {
                        throw new \InvalidArgumentException('Unhandled value type');
                }

                if (!isset($this->_suffix)) {
                        $this->_suffix = 'B';
                }

                switch ($this->_suffix) {
                        case 'B':
                                // ignore
                                break;
                        case 'K':
                        case 'M':
                        case 'G':
                                $this->_size *= self::$_sizes[$this->_suffix];
                                break;
                        default:
                                throw new \InvalidArgumentException('Unknown suffix type');
                }
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'size':
                                return $this->_size;
                        case 'suffix':
                                return $this->_suffix;
                }
        }

        public function __toString()
        {
                return sprintf("%d%s", $this->_size / self::$_sizes[$this->_suffix], $this->_suffix);
        }

        /**
         * Normalize size and suffix (i.e. 8192K => 8M).
         * @param Size $size The size object to normalize.
         */
        public static function normalize($size)
        {
                $curr = $last = 0;
                $suff = null;

                foreach (self::$_sizes as $suffix => $bytes) {
                        $curr = $size->size / $bytes;

                        if (is_float($curr)) {
                                break;
                        } else {
                                $last = $curr;
                                $suff = $suffix;
                        }
                }

                return new Size(array(
                        'size'   => $last,
                        'suffix' => $suff
                ));
        }

}
