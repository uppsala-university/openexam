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
                try {
                        $result = array();
                        $transaction = null;

                        if (!is_array($models)) {
                                $models = array($models);
                        }

                        if ($action != ObjectAccess::READ && count($models) > 1) {
                                $transactionManager = new TransactionManager();
                                $transactionManager->setDbService($models[0]->getWriteConnectionService());
                                $transaction = $transactionManager->get();
                        }

                        foreach ($models as $model) {
                                switch ($action) {
                                        case ObjectAccess::CREATE:
                                                if (isset($transaction)) {
                                                        $model->setTransaction($transaction);
                                                }
                                                $result[] = $this->create($model);
                                                break;
                                        case ObjectAccess::READ:
                                                $result[] = $this->read($model, $params);
                                                break;
                                        case ObjectAccess::UPDATE:
                                                if (isset($transaction)) {
                                                        $model->setTransaction($transaction);
                                                }
                                                $result = $this->update($model);
                                                break;
                                        case ObjectAccess::DELETE:
                                                if (isset($transaction)) {
                                                        $model->setTransaction($transaction);
                                                }
                                                $result = $this->delete($model);
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
                        $this->logger->system->error($exception->getMessage());
                        throw new Exception("Failed $action object.");
                } catch (Exception $exception) {
                        if (isset($transaction)) {
                                $transaction->rollback();
                        }
                        throw $exception;
                }
        }

        /**
         * Create model.
         * @param Model $model Input data.
         * @return Model The created model.
         * @throws Exception
         */
        public function create($model)
        {
                if ($model->create() == false) {
                        $this->error($model, ObjectAccess::CREATE);
                } else {
                        return $model;
                }
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
                if ($model->id != 0) {
                        $class = get_class($model);
                        return $class::findFirstById($model->id);
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
                                        if (is_string($val)) {
                                                $params['conditions'][] = array(
                                                        "$class.$key LIKE :$key:",
                                                        array($key => "%$val%"),
                                                        array($key => PDO::PARAM_STR)
                                                );
                                        } else {
                                                $params['conditions'][] = array(
                                                        "$class.$key = :$key:",
                                                        array($key => "$val")
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

                        foreach ($models as $m) {
                                $dump = array();
                                foreach ($m->dump() as $key => $val) {
                                        if (!is_object($val)) {
                                                $dump[$key] = $val;
                                        }
                                }
                                $result[] = $dump;
                        }

                        return $result;
                }
        }

        /**
         * Update model.
         * @param Model $model The model to update.
         * @return bool True if successful.
         * @throws Exception
         */
        public function update($model)
        {
                if ($model->update() == false) {
                        $this->error($model, ObjectAccess::UPDATE);
                } else {
                        return true;
                }
        }

        /**
         * Delete model.
         * @param Model $model The model to delete.
         * @return bool True if successful.
         * @throws Exception
         */
        public function delete($model)
        {
                if ($model->delete() == false) {
                        $this->error($model, ObjectAccess::DELETE);
                } else {
                        return true;
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

                throw new Exception("Failed $action object.");
        }

}
