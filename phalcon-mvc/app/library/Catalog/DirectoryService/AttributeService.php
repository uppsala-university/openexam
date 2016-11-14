<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AttributeService.php
// Created: 2016-11-14 23:20:32
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService;

use OpenExam\Library\Catalog\ServiceAdapter;

/**
 * Abstract base class for attribute services.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class AttributeService extends ServiceAdapter
{

        /**
         * Attribute map.
         * @var array 
         */
        protected $_attrmap;
        /**
         * The affiliation callback.
         * @var closure 
         */
        protected $_affiliation;

        /**
         * Constructor
         * @param array $attrmap The attribute map.
         */
        protected function __construct($attrmap)
        {
                $this->_attrmap = $attrmap;
                $this->_affiliation = function($attrs) {
                        return $attrs;
                };
        }

        /**
         * Set attribute map.
         * 
         * The attribute map can be used to remap the symbolic query attributes
         * constants defined by the Principal and Group class. The mapped
         * values are i.e. the object class attribute name as defined by the LDAP
         * schema.
         * 
         * <code>
         * $service->setAttributeMap(array(
         *      'person' => array(Principal::ATTR_UID => 'sAMAccountName'),
         *      'group'  => array(Group::ATTR_NAME    => 'cn')
         * ));
         * </code>
         * 
         * The $attrmap argument is merged with the existing attribute map.
         * 
         * @param array $attrmap The attribute map.
         */
        public function setAttributeMap($attrmap)
        {
                foreach ($attrmap as $class => $attributes) {
                        $this->_attrmap[$class] = array_merge($this->_attrmap[$class], $attributes);
                }
        }

        /**
         * Get current attribute map.
         * @return array
         */
        public function getAttributeMap()
        {
                return $this->_attrmap;
        }

        /**
         * Set user affiliation callback.
         * @param callable $callback The callback function.
         */
        public function setAffiliationCallback($callback)
        {
                $this->_affiliation = $callback;
        }

        /**
         * Set user affiliation map.
         * 
         * Calling this method replaces the current set callback.
         * 
         * <code>
         * $service->setAffiliationMap(array(
         *      Affiliation::EMPLOYEE => 'employee',
         *      Affiliation::STUDENT  => 'student'
         * ));
         * </code>
         * 
         * @param array $affiliation The affiliation map.
         */
        public function setAffiliationMap($map)
        {
                $this->_affiliation = function($attrs) use($map) {
                        $result = array();
                        foreach ($map as $key => $values) {
                                if (!is_array($values)) {
                                        $values = array($values);
                                }
                                foreach ($values as $val) {
                                        foreach ($attrs as $index => $attr) {
                                                if ($attr == $val) {
                                                        $result[$key] = true;
                                                }
                                        }
                                }
                        }
                        return array_keys($result);
                };
        }

}
