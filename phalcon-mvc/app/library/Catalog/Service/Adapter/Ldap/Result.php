<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Result.php
// Created: 2014-10-22 04:21:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service\Adapter\Ldap;

/**
 * LDAP result class.
 */
class Result
{

        /**
         * The reverse attribute map.
         * @var array 
         */
        private $_attrmap;
        /**
         * The result array.
         * @var array 
         */
        private $_result = array();
        /**
         * The service name.
         * @var string 
         */
        private $_name;
        /**
         * The service type.
         * @var string
         */
        private $_type;

        /**
         * Constructor.
         * @param array $attrmap The reverse attribute map.
         * @param string $name The service name.
         * @param string $type The service type.
         */
        public function __construct($attrmap, $name = null, $type = 'ldap')
        {
                $this->_attrmap = array_change_key_case($attrmap);
                $this->_name = $name;
                $this->_type = $type;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_attrmap);
                unset($this->_result);
                unset($this->_name);
                unset($this->_type);
        }

        /**
         * Set service name.
         * @param string $name The service name.
         */
        public function setName($name)
        {
                $this->_name = $name;
        }

        /**
         * Set service type.
         * @param string $type The service type.
         */
        public function setType($type)
        {
                $this->_type = $type;
        }

        /**
         * Get result array.
         * @return array
         */
        public function getResult()
        {
                return $this->_result;
        }

        /**
         * Returns the number of records in result.
         * @return int
         */
        public function count()
        {
                return count($this->_result);
        }

        /**
         * Reset internal state.
         * @param array $attrmap The reverse attribute map.
         */
        public function reset($attrmap)
        {
                $this->_result = array();
                $this->_attrmap = array_change_key_case($attrmap);
        }

        /**
         * Append directory entries.
         * @param array $entries The directory entries.
         */
        public function append($entries)
        {
                for ($i = 0; $i < $entries['count']; $i++) {
                        $this->addEntry($i, $entries[$i]);
                }
        }

        /**
         * Insert directory entries.
         * @param array $entries The directory entries.
         */
        public function insert($entries)
        {
                $this->_result = array();
                $this->append($entries);
        }

        /**
         * Replace directory entries.
         * @param array $entries The directory entries.
         * @param array $attrmap The reverse attribute map.
         */
        public function replace($entries, $attrmap)
        {
                $this->reset($attrmap);
                $this->append($entries);
        }

        /**
         * Add directory entry.
         * @param array $entry
         */
        private function addEntry($index, $entry)
        {
                // 
                // Set service data reference:
                // 
                $this->_result[$index]['svc'] = array(
                        'name' => $this->_name,
                        'type' => $this->_type,
                        'ref'  => $entry['dn']
                );
                // 
                // Polulate entry data:
                // 
                for ($i = 0; $i < $entry['count']; $i++) {
                        $name = $entry[$i];
                        $attr = $entry[$name];

                        $this->addAttribute($index, $name, $attr);

                        unset($name);
                        unset($attr);
                }
        }

        /**
         * Add attribute values at index $index.
         * @param string|int $index The containing index.
         * @param string $name The attribute name.
         * @param array $attr The attribute value.
         */
        private function addAttribute($index, $name, $attr)
        {
                // 
                // Split on attribute name/extension:
                // 
                if (strpos($name, ';')) {
                        list($name, $type) = explode(";", $name);
                }

                // 
                // Get reverse mapped attribute name:
                // 
                if (isset($this->_attrmap[$name])) {
                        $name = $this->_attrmap[$name];
                }

                // 
                // Add attribute data to result:
                // 
                for ($i = 0; $i < $attr['count']; $i++) {
                        if (!isset($this->_result[$index][$name])) {
                                $this->_result[$index][$name] = array();
                        }
                        if (!in_array($attr[$i], $this->_result[$index][$name])) {
                                $this->_result[$index][$name][] = $attr[$i];
                        }
                        if (isset($type)) {
                                $this->_result[$index][$name][$type] = $attr[$i];
                        }
                }
        }

}
