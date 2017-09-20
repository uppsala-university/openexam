<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelBase.php
// Created: 2014-02-24 16:36:39
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Database\Adapter\Deferred\DeferredAdapter;
use OpenExam\Library\Database\Cache\Mediator;
use OpenExam\Library\Model\Audit\Audit;
use OpenExam\Library\Model\Audit\History;
use OpenExam\Library\Model\Filter;
use OpenExam\Library\Security\Roles;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Db\AdapterInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\TransactionInterface;
use Phalcon\Mvc\ModelInterface;

/**
 * Base class for all models.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelBase extends Model
{

        protected function initialize()
        {
                if (defined('MODEL_ALWAYS_USE_MASTER_CONNECTION') && MODEL_ALWAYS_USE_MASTER_CONNECTION) {
                        $this->setReadConnectionService('dbwrite');
                        $this->setWriteConnectionService('dbwrite');
                } else {
                        $this->setReadConnectionService('dbread');
                        $this->setWriteConnectionService('dbwrite');
                }

                if ($this->getDI()->has('audit')) {
                        $audit = $this->getDI()->get('audit');
                        $model = $this->getResourceName();

                        if ($audit->hasConfig($model)) {
                                if (($config = $audit->getConfig($model))) {
                                        $this->addBehavior(new Audit($config));
                                        $this->keepSnapshots(true);
                                }
                        }
                }
        }

        /**
         * Get model history.
         * @return History
         */
        public function getHistory()
        {
                return new History($this);
        }

        /**
         * Get model resource name.
         * @return string
         */
        public function getResourceName()
        {
                return strtolower(substr(strrchr(get_class($this), "\\"), 1));
        }

        /**
         * Get relation map.
         * @param string $resource The resource name.
         * @param string $leftcol The left hand column.
         * @param string $rightcol The right hand column.
         * @param string $rightres The right hand resource.
         * @return string
         */
        public static function getRelation($resource, $leftcol = null, $rightcol = null, $rightres = null)
        {
                if (isset($rightres)) {
                        return
                            __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol . '=' .
                            __NAMESPACE__ . '\\' . ucfirst($rightres) . '.' . $rightcol;
                } elseif (isset($rightcol)) {
                        return __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol . '=' . $rightcol;
                } elseif (isset($leftcol)) {
                        return __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol;
                } else {
                        return __NAMESPACE__ . '\\' . ucfirst($resource);
                }
        }

        /**
         * Prepare query parameters. 
         * 
         * We have to support parameters as passed to both query builder and 
         * find. To add complexity, sometimes a comma separated list defines
         * them and sometimes it uses an array.
         * 
         * @param string $class The name of the model class.
         * @param array $parameters The query parameters.
         * @return array
         */
        protected static function getParameters($class, $parameters)
        {
                // 
                // Handle conditions (e.g. name = ?0 or name = :name:):
                // 
                if (isset($parameters['conditions'])) {
                        if (is_string($parameters['conditions'])) {
                                $parameters['conditions'] = preg_replace("/(\[?[a-z\\\\]+\]?\.)?(\[?\w+?\]?) (=|between|in)/i", "${class}.$2 $3", $parameters['conditions']);
                        }
                }

                // 
                // Handle e.g. findFirst() or relational arguments:
                // 
                foreach (array_keys($parameters) as $index) {
                        if (is_int($index)) {
                                $parameters[$index] = preg_replace("/(\[?[a-z\\\\]+\]?\.)?(\[?\w+?\]?) (=|between|in)/i", "${class}.$2 $3", $parameters[$index]);
                        }
                }

                // 
                // Handle order conditions:
                // 
                if (isset($parameters['order'])) {
                        if (is_string($parameters['order'])) {
                                $parameters['order'] = explode(",", $parameters['order']);
                        }
                        foreach ($parameters['order'] as $index => $value) {
                                if (strpos($value, '.')) {
                                        continue;
                                }
                                $parameters['order'][$index] = $class . '.' . $parameters['order'][$index];
                        }
                }

                return $parameters;
        }

        /**
         * Get model access control object.
         * @return ObjectAccess
         */
        public function getObjectAccess()
        {
                $class = sprintf("OpenExam\Plugins\Security\Model\%sAccess", ucfirst($this->getResourceName()));
                return new $class();
        }

        /**
         * Get filter for result set.
         * @param array $params The query parameters.
         * @return Filter The result set filter object.
         */
        public function getFilter($params)
        {
                return null;
        }

        /**
         * Let our custom event manager handle model access control by fire
         * an after fetch event on this model.
         */
        protected function afterFetch()
        {
                $modelsManager = $this->getModelsManager();
                $eventsManager = $modelsManager->getEventsManager();
                $eventsManager->fire('model:afterFetch', $this);
        }

        /**
         * Inserts or updates a model instance. Returning true on success or 
         * false otherwise. 
         * 
         * This method overrides Phalcon's save() method by adding retry 
         * behavior trying to persist the model 4 times during a total period 
         * of 3 sec before failing.
         * 
         * Returns true if successful saved or false if validation fails or
         * if unsaved after all retries.
         * 
         * <code>
         * // Creating a new robot 
         * $robot = new Robots(); 
         * $robot->type = 'mechanical'; 
         * $robot->name = 'Astro Boy'; 
         * $robot->year = 1952; 
         * $robot->save();  
         * </code>
         * 
         * <code>
         * // Updating a robot name:
         * $robot = Robots::findFirst("id=100"); 
         * $robot->name = "Biomass"; 
         * $robot->save();  
         * </code>
         * 
         * @param array $data
         * @param array $whiteList
         * @return boolean 
         */
        public function save($data = null, $whiteList = null)
        {
                $retry = array(400000, 600000, 1000000, 1000000);

                for ($i = 0; $i < count($retry); $i++) {
                        if (parent::save($data, $whiteList)) {
                                return true;
                        }
                        if (parent::validationHasFailed()) {
                                return false;
                        } else {
                                usleep($retry[$i]);
                        }
                }

                return false;
        }

        /**
         * Called after model was deleted.
         */
        protected function afterDelete()
        {
                $this->invalidate();
        }

        /**
         * Called before validation on update.
         * 
         * This function allows update of sparse model objects (not having
         * all required attributes) as these are fetched from existing model
         * if required.
         * 
         * If this model has all required attributes, then except for overhead
         * of validating this is a noop with minimal performance hit.
         */
        protected function beforeValidationOnUpdate()
        {
                // 
                // Get required fields:
                // 
                $notNullAttributes = $this->getModelsMetaData()->getNotNullAttributes($this);

                // 
                // Get reverse column map (field -> attr):
                // 
                $columnMap = $this->getModelsMetaData()->getColumnMap($this);

                // 
                // Create array of required attributes:
                // 
                $required = array_map(
                    function($field) use($columnMap) {
                        return $columnMap[$field];
                }, $notNullAttributes);

                // 
                // Check if this model need to be populated:
                // 
                foreach ($required as $attr) {
                        if (!isset($this->$attr)) {
                                $populate = true;
                                break;
                        }
                }

                // 
                // Nothing to do:
                // 
                if (!isset($populate)) {
                        return;
                }

                // 
                // Find existing model (same ID):
                // 
                $class = get_class($this);
                $model = $class::findFirst("id = $this->id");

                // 
                // Merge this model with the existing:
                // 
                foreach ($required as $attr) {
                        if (!isset($this->$attr) && isset($model->$attr)) {
                                $this->$attr = $model->$attr;
                        }
                }

                // 
                // Now it gets even more ugly:
                // 
                $attributes = get_object_vars($this);
                foreach (array_keys($attributes) as $attr) {
                        if (!isset($this->$attr) && isset($model->$attr)) {
                                $this->$attr = $model->$attr;
                        }
                }
        }

        /**
         * Saves related records that must be stored prior to save the 
         * master record.
         * 
         * @param AdapterInterface $connection
         * @param ModelInterface[] $related
         * @return bool 
         */
        protected function _preSaveRelatedRecords($connection, $related)
        {
                // 
                // Get database adapter if using cache mediator:
                // 
                if ($connection instanceof Mediator) {
                        $adapter = $connection->getHandler()->getAdapter();
                } else {
                        $adapter = $connection;
                }

                // 
                // Get underlying adapter from deferred:
                // 
                if ($adapter instanceof DeferredAdapter) {
                        $adapter = $adapter->getAdapter();
                }

                // 
                // Only perform access control on the master record. Bypass
                // ACL for related records using the system role.
                // 
                $user = $this->getDI()->get('user');
                $role = $user->setPrimaryRole(Roles::SYSTEM);

                $txlevel = $adapter->getTransactionLevel();

                try {
                        if (($result = parent::_preSaveRelatedRecords($adapter, $related))) {
                                $this->_related = null;
                        } else {
                                $this->_related = $related;
                        }
                } catch (\Exception $exception) {
                        while ($adapter->getTransactionLevel() > $txlevel) {
                                try {
                                        $adapter->rollback();
                                } catch (\PDOException $e) {
                                        // ignore
                                }
                        }
                        $this->_related = $related;
                        $user->setPrimaryRole($role);
                        throw $exception;
                }

                $user->setPrimaryRole($role);
                return $result;
        }

        /**
         * Check if related records exists.
         */
        public function hasRelatedRecords()
        {
                return count($this->_related) > 0;
        }

        /**
         * Get related records.
         * @return array
         * @see getRelated()
         */
        public function getRelatedRecords()
        {
                return $this->_related;
        }

        /**
         * Reset the array of related records for this model.
         */
        public function resetRelatedRecords()
        {
                $this->_related = array();
        }

        /**
         * Get active transaction.
         * @return TransactionInterface
         */
        public function getTransaction()
        {
                return $this->_transaction;
        }

        /**
         * Set active transaction.
         * @param TransactionInterface $transaction The active transaction.
         */
        public function setTransaction(TransactionInterface $transaction)
        {
                $this->setReadConnectionService('dbwrite');
                parent::setTransaction($transaction);
        }

        /**
         * Enable transaction mode for this model.
         */
        public function useTransaction()
        {
                $this->setReadConnectionService('dbwrite');
        }

        /**
         * Enable transactions mode for all models.
         */
        public static function useTransactions()
        {
                // 
                // This enables transaction mode for all models, even those
                // that is not part of the transaction (use with care)!.
                // 
                if (!defined('MODEL_ALWAYS_USE_MASTER_CONNECTION')) {
                        define('MODEL_ALWAYS_USE_MASTER_CONNECTION', true);
                }
        }

        /**
         * Invalidate this model.
         * @return boolean True if invalidated.
         */
        public function invalidate()
        {
                if (!($adapter = $this->getWriteConnection())) {
                        return true;
                } elseif (!($adapter instanceof Mediator)) {
                        return true;
                } elseif (!$adapter->canInvalidate()) {
                        return true;
                }

                $cache = $adapter->getHandler()->getCache();
                $table = $this->getSource();

                return $cache->invalidate($table, $this->id);
        }

}
