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

use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset;
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
                $class = sprintf("\OpenExam\Models\%s", ucfirst($name));
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
         * @return mixed 
         * @throws Exception
         */
        public function action($models, $action)
        {
                try {
                        $result = array();
                        $transaction = null;

                        if (!is_array($models)) {
                                $models = array($models);
                        }

                        if ($action != ObjectAccess::READ && count($models) > 1) {
                                $transactionManager = new TransactionManager();
                                $transactionManager->setDbService($models[0]->getReadConnectionService());
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
                                                $result[] = $this->read($model);
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
                                }
                        }
                        if (isset($transaction)) {
                                $transaction->commit();
                        }
                        return $result;
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
         * The behavour depends on whether the model ID is set or not. 
         * 
         * If ID is set and != 0, then the associated object is returned from 
         * the database. 
         * 
         * If ID is unset, then the model properties is treated as query
         * parameters. The query is a simple natural join on defined values
         * in the model object.
         * 
         * @param Model $model
         * @return Resultset
         * @throws Exception
         */
        public function read($model)
        {
                if ($model->id != 0) {
                        $class = get_class($model);
                        $result = $class::findFirstById($model->id);
                        return $result;
                } else {
                        $criteria = $model->query()->fromInput($this->getDI(), get_class($model), $model->dump());
                        return $criteria->execute()->toArray();
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
