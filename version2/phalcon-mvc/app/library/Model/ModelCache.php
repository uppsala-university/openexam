<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelCache.php
// Created: 2016-01-28 17:54:05
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model;

use Phalcon\Cache\BackendInterface;
use Phalcon\Logger;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\User\Component;

/**
 * Handle cache of resusable models.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelCache extends Component
{

        /**
         * The caching backend.
         * @var BackendInterface 
         */
        private $_cache;

        /**
         * Constructor.
         * @param BackendInterface|string $cache The backend interface.
         */
        public function __construct($cache)
        {
                if ($cache instanceof BackendInterface) {
                        $this->_cache = $cache;
                } else {
                        $cache = $this->getDI()->get($cache);
                        $this->_cache = $cache;
                }
        }

        /**
         * Check if key exist in cache.
         * @param string $key The cache key.
         * @return bool
         */
        public function exist($key)
        {
                if ($this->logger->cache &&
                    $this->logger->cache->getLogLevel() <= Logger::DEBUG) {
                        $this->logger->cache->debug(sprintf("Check cache key %s", $key));
                }

                return $this->_cache->exists($key);
        }

        /**
         * Get object associated with cache key.
         * @param string $key The cache key.
         * @return object
         */
        public function get($key)
        {
                if ($this->logger->cache &&
                    $this->logger->cache->getLogLevel() <= Logger::DEBUG) {
                        $this->logger->cache->debug(sprintf("Query cache key %s", $key));
                }

                return $this->_cache->get($key);
        }

        /**
         * Save content associated with cache key.
         * @param string $key The cache key.
         * @param object $content The cached data.
         */
        public function save($key, $content)
        {
                if ($this->logger->cache &&
                    $this->logger->cache->getLogLevel() <= Logger::DEBUG) {
                        $this->logger->cache->info(sprintf("Write cache key %s", $key));
                }

                $this->_cache->save($key, $content);
        }

        /**
         * Invalidate cache related to this model.
         * 
         * This function can be called whenever a model is created, updated
         * or deleted. It serves two different purposes:
         * 
         * 1. Cleanup cached relation data to save memory (has many/has one).
         * 2. Force reload of relation data on model change (belongs to).
         * 
         * Example:
         * -------------
         * 
         * When student on an exam is modified, then we need to invalidate 
         * the list of cached student to force reload from the database. 
         * 
         * Use the model relation and data to generate a cache key to delete 
         * entries like model-related-student-exam_id-3685 from cache.
         * 
         * @param ModelInterface $model The model object.
         */
        public function invalidate($model)
        {
                if ($this->logger->cache &&
                    $this->logger->cache->getLogLevel() <= Logger::DEBUG) {
                        $this->logger->cache->debug(sprintf("Invalidate model %s(%d)", $model->getResourceName(), $model->id));
                }

                $this->cleanupRelations($model, $this->modelsManager->getHasMany($model));
                $this->cleanupRelations($model, $this->modelsManager->getHasOne($model));
                $this->cleanupRelations($model, $this->modelsManager->getBelongsTo($model));
        }

        /**
         * Cleanup model relation cache.
         * @param ModelInterface $model The model object.
         * @param array $relations The model relations.
         */
        private function cleanupRelations($model, $relations)
        {
                if (count($relations) != 0) {
                        foreach ($relations as $relation) {
                                $options = $relation->getOptions();
                                if (isset($options['reusable']) && $options['reusable']) {
                                        $this->cleanupRelation($model, $relation);
                                }
                        }
                }
        }

        /**
         * Cleanup model relation cache.
         * @param ModelInterface $model The model object.
         * @param RelationInterface $relation The model relation.
         */
        private function cleanupRelation($model, $relation)
        {
                $key = self::createCacheKey($model, $relation);

                if ($this->_cache->exists($key)) {
                        if ($this->logger->cache) {
                                $this->logger->cache->info(sprintf("Deleting model cache %s", $key));
                        }
                        $this->_cache->delete($key);
                }
        }

        /**
         * Create cache key.
         * @param ModelInterface $model The model object.
         * @param RelationInterface $relation The model relation.
         */
        private static function createCacheKey($model, $relation)
        {
                switch ($relation->getType()) {
                        case Relation::HAS_MANY:
                        case Relation::HAS_ONE:
                                $n = self::getResourceName($relation->getReferencedModel());
                                $f = $relation->getReferencedFields();
                                return sprintf("model-related-%s-%s-%d", $n, $f, $model->id);
                        case Relation::BELONGS_TO:
                                $n = $model->getResourceName();
                                $f = $relation->getFields();
                                return sprintf("model-related-%s-%s-%d", $n, $f, $model->$f);
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
