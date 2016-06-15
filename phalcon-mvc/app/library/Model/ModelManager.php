<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelManager.php
// Created: 2016-01-28 01:03:41
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model;

use Phalcon\Mvc\Model\Manager;
use Phalcon\Mvc\ModelInterface;

/**
 * Custom model manager.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelManager extends Manager
{

        /**
         * The models cache.
         * @var ModelCache 
         */
        private $_cache;

        /**
         * Constructor
         * @param ModelCache|string $cache Name of cache service.
         */
        public function __construct($cache = 'modelsCache')
        {
                if ($cache instanceof ModelCache) {
                        $this->_cache = $cache;
                } else {
                        $this->_cache = new ModelCache($cache);
                }
        }

        /**
         * Returns a reusable object from the internal list
         *
         * @param string $modelName
         * @param string $key
         * @return object
         */
        public function getReusableRecords($modelName, $key)
        {
                if (($spec = self::createCacheKey($modelName, $key))) {
                        if ($this->_cache->exist($spec)) {
                                return $this->_cache->get($spec);
                        }
                }

                return parent::getReusableRecords($modelName, $key);
        }

        /**
         * Stores a reusable record in the internal list
         *
         * @param string $modelName
         * @param string $key
         * @param mixed $records 
         */
        public function setReusableRecords($modelName, $key, $records)
        {
                if (($spec = self::createCacheKey($modelName, $key))) {
                        $this->_cache->save($spec, $records);
                        return;
                }

                parent::setReusableRecords($modelName, $key, $records);
        }

        /**
         * Handle model events.
         * @param string $eventName
         * @param ModelInterface $model
         */
        public function notifyEvent($eventName, ModelInterface $model)
        {
                if ($eventName == 'afterCreate' ||
                    $eventName == 'afterUpdate' ||
                    $eventName == 'afterDelete') {
                        $this->_cache->invalidate($model);
                }

                parent::notifyEvent($eventName, $model);
        }

        /**
         * Create special cache key.
         * @param string $modelName The model class name.
         * @param string $key The cache key.
         * @return string
         */
        private static function createCacheKey($modelName, $key)
        {
                $data = self::getRelationData($key);
                $name = self::getResourceName($modelName);

                if ($data) {
                        return sprintf("model-related-%s-%s-%d", $name, $data[1], $data[2]);
                } else {
                        return false;
                }
        }

        /**
         * Extract relation data.
         * @param string $key The cache key.
         * @return boolean|array
         */
        private static function getRelationData($key)
        {
                $pattern = "/.*\[\[\[(.*?)\].*\[(.*?)\].*/";
                $matches = array();

                if (preg_match($pattern, $key, $matches)) {
                        return $matches;
                } else {
                        return false;
                }
        }

        /**
         * Get model resource name.
         * @param string $modelName The model class name.
         * @return string
         */
        private static function getResourceName($modelName)
        {
                return strtolower(substr(strrchr($modelName, "\\"), 1));
        }

}
