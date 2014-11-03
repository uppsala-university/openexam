<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CoreHandler.php
// Created: 2014-09-30 10:09:52
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Handler;

use OpenExam\Models\ModelBase;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use PDO;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Transaction\Failed as TransactionFailed;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Mvc\Model\TransactionInterface;
use Phalcon\Mvc\User\Component;

/**
 * Core service handler.
 * 
 * This class acts as the glue between AJAX/REST/SOAP services and the
 * model. It provides an interface for CRUD-operations against all models
 * and their underlying tables.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CoreHandler extends Component
{

        /**
         * Inline count in result:
         */
        const COUNT_INLINE = 'inline';
        /**
         * Use simple count on result:
         */
        const COUNT_SIMPLE = true;
        /**
         * Don't count anything:
         */
        const COUNT_NOTHING = false;
        /**
         * Default count policy:
         */
        const COUNT_DEFAULT = self::COUNT_NOTHING;

        /**
         * Constructor.
         * @param string $role The prefered role.
         */
        public function __construct($role)
        {
                $this->getDI()->get('user')->setPrimaryRole($role);
        }

        /**
         * Build model object from name and data.
         * @param string $name The model name (lower case).
         * @param array $data The model data.'
         * @return Model 
         */
        public function build($name, $data)
        {
                $class = ModelBase::getRelation($name);
                if (!class_exists($class)) {
                        throw new Exception("Failed map request target.");
                }
                $model = new $class();
                $model->assign($data);
                return $model;
        }

        /**
         * Perform action.
         * 
         * This function uses database transactions if number of models are
         * greater than one. Requested action is etiher performed on all
         * models or none (rollback).
         * 
         * @param Model[] $models The input models.
         * @param string $action The action to perform.
         * @param array $params Optional parameters for read action.
         * 
         * @return mixed 
         * @throws Exception
         * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Model_Query_Builder.html
         */
        public function action($models, $action, $params = array())
        {
                $result = array();
                $transaction = null;

                try {
                        if (!is_array($models)) {
                                $models = array($models);
                        }

                        if ($action != ObjectAccess::READ && count($models) > 1) {
                                $transactionManager = new TransactionManager();
                                $transactionManager->setDbService('dbwrite');
                                $transaction = $transactionManager->get();
                        }

                        foreach ($models as $model) {
                                switch ($action) {
                                        case ObjectAccess::CREATE:
                                                $result[] = $this->create($model, $transaction);
                                                break;
                                        case ObjectAccess::READ:
                                                $result[] = $this->read($model, $params);
                                                break;
                                        case ObjectAccess::UPDATE:
                                                $result = $this->update($model, $transaction);
                                                break;
                                        case ObjectAccess::DELETE:
                                                $result = $this->delete($model, $transaction);
                                                break;
                                        default:
                                                throw new Exception("The method $action don't exist.");
                                }
                        }
                        if (isset($transaction)) {
                                $transaction->commit();
                        }
                        if (is_array($result) && count($result) == 1) {
                                return $result[0];
                        } else {
                                return $result;
                        }
                } catch (TransactionFailed $exception) {
                        $this->report($exception, $model);
                        throw $exception;
                } catch (Exception $exception) {
                        $this->report($exception, $model);
                        if (isset($transaction)) {
                                $transaction->rollback($exception->getMessage());
                        } else {
                                throw $exception;
                        }
                }
        }

        /**
         * Create model.
         * @param Model $model Input data.
         * @param TransactionInterface $transaction The transaction.
         * @return Model The created model.
         * @throws Exception
         */
        public function create($model, $transaction)
        {
                if ($model->getDI() == null) {
                        $model = self::instantiate($model);
                }
                if (isset($transaction)) {
                        $model->setTransaction($transaction);
                }
                if ($model->create() == false) {
                        $this->error($model, ObjectAccess::CREATE);
                } else {
                        return $model;
                }
        }

        /**
         * Update model.
         * @param Model $model The model to update.
         * @param TransactionInterface $transaction The transaction.
         * @return bool True if successful.
         * @throws Exception
         */
        public function update($model, $transaction)
        {
                if ($model->getDI() == null) {
                        $model = self::instantiate($model);
                }
                if (isset($transaction)) {
                        $model->setTransaction($transaction);
                }
                if ($model->update() == false) {
                        $this->error($model, ObjectAccess::UPDATE);
                } else {
                        return true;
                }
        }

        /**
         * Delete model.
         * @param Model $model The model to delete.
         * @param TransactionInterface $transaction The transaction.
         * @return bool True if successful.
         * @throws Exception
         */
        public function delete($model, $transaction)
        {
                if ($model->getDI() == null) {
                        $model = self::instantiate($model);
                }
                if (isset($transaction)) {
                        $model->setTransaction($transaction);
                }
                if ($model->delete() == false) {
                        $this->error($model, ObjectAccess::DELETE);
                } else {
                        return true;
                }
        }

        /**
         * Instantiate partial constructed model object.
         * @param Model $model The partial constructed object.
         * @return Model
         */
        private static function instantiate($model)
        {
                $array = $model->dump();
                $class = get_class($model);
                $model = new $class();
                $model->assign($array);
                return $model;
        }

        /**
         * Query model.
         * 
         * The behavour depends on whether the model ID is set or not. If the 
         * ID != 0, then the single object having that ID is returned. If the
         * ID == 0, then a result set is returned where the model argument is
         * used for defining a simple where query.
         * 
         * If called with primary role set and if the argument is an exam or 
         * question model object, then the returned objects is restricted to
         * the calling user and role.
         * 
         * @param Model $model
         * @return array|Model
         * @throws Exception
         */
        public function read($model, $params = array())
        {
                // 
                // Set default count policy:
                // 
                if (!isset($params['count'])) {
                        $params['count'] = self::COUNT_DEFAULT;
                }

                // 
                // Strip relations from model.
                // 
                $strip = function($model) {
                        if (!($model instanceof \Phalcon\Mvc\Model)) {
                                throw new Exception("Expected model object");
                        }
                        $dump = array();
                        foreach ($model->dump() as $key => $val) {
                                if (!is_object($val)) {
                                        $dump[$key] = $val;
                                }
                        }
                        return $dump;
                };

                if ($model->id != 0) {
                        $class = get_class($model);
                        return $strip($class::findFirstById($model->id));
                } else {
                        $class = get_class($model);

                        // 
                        // Create conditions from model values:
                        // 
                        foreach ($model->dump() as $key => $val) {
                                if (isset($val)) {
                                        if (!isset($params['conditions'])) {
                                                $params['conditions'] = array();
                                        }
                                        if (is_numeric($val)) {
                                                $params['conditions'][] = array(
                                                        "$class.$key = :$key:",
                                                        array($key => "$val")
                                                );
                                        } elseif (is_string($val)) {
                                                $params['conditions'][] = array(
                                                        "$class.$key LIKE :$key:",
                                                        array($key => "%$val%"),
                                                        array($key => PDO::PARAM_STR)
                                                );
                                        }
                                }
                        }

                        // 
                        // Get matching models, possibly taking roles into account
                        // and collect requested model data excluding any relations
                        // with other models.
                        // 

                        $models = $class::find($params);
                        $result = array();

                        // 
                        // Process non-empty resultset:
                        // 
                        if (count($models) != 0) {
                                $filter = $models->getFirst()->getFilter($params);

                                if (isset($filter)) {
                                        $result = $models->filter($filter);
                                } else {
                                        foreach ($models as $m) {
                                                $result[] = $strip($m);
                                        }
                                }
                        }

                        // 
                        // Return count on resultset or simply the result:
                        // 
                        if ($params['count'] === self::COUNT_INLINE) {
                                return array('count' => count($result), 'result' => $result);
                        } elseif ($params['count'] === self::COUNT_SIMPLE) {
                                return count($result);
                        } elseif ($params['count'] === self::COUNT_NOTHING) {
                                return $result;
                        } else {
                                throw new Exception("Unknown argument for count.");
                        }
                }
        }

        /**
         * Error handler.
         * @param Model $model
         * @param string $action
         * @throws Exception
         */
        private function error($model, $action)
        {
                $this->logger->system->begin();
                foreach ($model->getMessages() as $message) {
                        $this->logger->system->error($message);
                }
                $this->logger->system->commit();

                throw new Exception("Failed $action object ($message).");
        }

        /**
         * Report exception.
         * @param Exception $exception The exception to report.
         * @param Model $model The current model.
         */
        private function report($exception, $model)
        {
                $this->logger->system->begin();
                $this->logger->system->error(
                    print_r(array(
                        'Exception' => get_class($exception),
                        'Message'   => $exception->getMessage(),
                        'Model'     => get_class($model),
                        'Data'      => print_r($model->dump(), true)
                        ), true
                    )
                );
                $this->logger->system->commit();
        }

}
