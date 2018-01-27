<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Proxy.php
// Created: 2017-01-16 00:04:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache;

use Phalcon\Db\AdapterInterface;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\DialectInterface;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\ReferenceInterface;

/**
 * Abstract database adapter proxy.
 * 
 * Call methods in the database adapter. Solves the problem with missing methods 
 * in the mediator class by proxy them to the wrapped database adapter using magic 
 * call.
 * 
 * It is not sufficient to have the magic call method. Even trough if will make 
 * correct calls on wrapped database adapter, the interface has to have concrete
 * methods. It's not sufficient to just declare magic methods.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class Proxy implements AdapterInterface
{

        /**
         * The database adapter.
         * @var AdapterInterface
         */
        protected $_adapter;
        /**
         * The adapter dialect.
         * @var DialectInterface 
         */
        private $_dialect;
        /**
         * The adapter type.
         * @var string 
         */
        private $_type;

        /**
         * Constructor.
         */
        public function __construct(array $descriptor)
        {
                
        }

        public function __call($name, $arguments)
        {
                if ($name == 'gettype') {
                        if (!isset($this->_type)) {
                                return $this->_type = call_user_func_array(array($this->_adapter, $name), $arguments);
                        } else {
                                return $this->_type;
                        }
                } elseif ($name == 'getdialect') {
                        if (!isset($this->_dialect)) {
                                return $this->_dialect = call_user_func_array(array($this->_adapter, $name), $arguments);
                        } else {
                                return $this->_dialect;
                        }
                } else {
                        return call_user_func_array(array($this->_adapter, $name), $arguments);
                }
        }

        public function addColumn($tableName, $schemaName, ColumnInterface $column)
        {
                return $this->_adapter->addColumn($tableName, $schemaName, $column);
        }

        public function addForeignKey($tableName, $schemaName, ReferenceInterface $reference)
        {
                return $this->_adapter->addForeignKey($tableName, $schemaName, $reference);
        }

        public function addIndex($tableName, $schemaName, IndexInterface $index)
        {
                return $this->_adapter->addIndex($tableName, $schemaName, $index);
        }

        public function addPrimaryKey($tableName, $schemaName, IndexInterface $index)
        {
                return $this->_adapter->addPrimaryKey($tableName, $schemaName, $index);
        }

        public function affectedRows()
        {
                return $this->_adapter->affectedRows();
        }

        public function begin($nesting = null)
        {
                return $this->_adapter->begin($nesting);
        }

        public function close()
        {
                return $this->_adapter->close();
        }

        public function commit($nesting = null)
        {
                return $this->_adapter->commit($nesting);
        }

        public function connect(array $descriptor = null)
        {
                return $this->_adapter->connect($descriptor);
        }

        public function createSavepoint($name)
        {
                return $this->_adapter->createSavepoint($name);
        }

        public function createTable($tableName, $schemaName, array $definition)
        {
                return $this->_adapter->createTable($tableName, $schemaName, $definition);
        }

        public function createView($viewName, array $definition, $schemaName = null)
        {
                return $this->_adapter->createView($viewName, $definition, $schemaName);
        }

        public function describeColumns($table, $schema = null)
        {
                return $this->_adapter->describeColumns($table, $schema);
        }

        public function describeIndexes($table, $schema = null)
        {
                return $this->_adapter->describeIndexes($table, $schema);
        }

        public function describeReferences($table, $schema = null)
        {
                return $this->_adapter->describeReferences($table, $schema);
        }

        public function dropColumn($tableName, $schemaName, $columnName)
        {
                return $this->_adapter->dropColumn($tableName, $schemaName, $columnName);
        }

        public function dropForeignKey($tableName, $schemaName, $referenceName)
        {
                return $this->_adapter->dropForeignKey($tableName, $schemaName, $referenceName);
        }

        public function dropIndex($tableName, $schemaName, $indexName)
        {
                return $this->_adapter->dropIndex($tableName, $schemaName, $indexName);
        }

        public function dropPrimaryKey($tableName, $schemaName)
        {
                return $this->_adapter->dropPrimaryKey($tableName, $schemaName);
        }

        public function dropTable($tableName, $schemaName = null, $ifExists = null)
        {
                return $this->_adapter->dropTable($tableName, $schemaName, $ifExists);
        }

        public function dropView($viewName, $schemaName = null, $ifExists = null)
        {
                return $this->_adapter->dropView($viewName, $schemaName, $ifExists);
        }

        public function escapeIdentifier($identifier)
        {
                return $this->_adapter->escapeIdentifier($identifier);
        }

        public function escapeString($str)
        {
                return $this->_adapter->escapeString($str);
        }

        public function execute($sqlStatement, $placeholders = null, $dataTypes = null)
        {
                return $this->_adapter->execute($sqlStatement, $placeholders, $dataTypes);
        }

        public function fetchAll($sqlQuery, $fetchMode = null, $placeholders = null)
        {
                return $this->_adapter->fetchAll($sqlQuery, $fetchMode, $placeholders);
        }

        public function fetchOne($sqlQuery, $fetchMode = null, $placeholders = null)
        {
                return $this->_adapter->fetchOne($sqlQuery, $fetchMode, $placeholders);
        }

        public function forUpdate($sqlQuery)
        {
                return $this->_adapter->forUpdate($sqlQuery);
        }

        public function getColumnDefinition(ColumnInterface $column)
        {
                return $this->_adapter->getColumnDefinition($column);
        }

        public function getColumnList($columnList)
        {
                return $this->_adapter->getColumnList($columnList);
        }

        public function getConnectionId()
        {
                return $this->_adapter->getConnectionId();
        }

        public function getDefaultIdValue()
        {
                return $this->_adapter->getDefaultIdValue();
        }

        public function getDescriptor()
        {
                return $this->_adapter->getDescriptor();
        }

        public function getDialect()
        {
                return $this->_adapter->getDialect();
        }

        public function getDialectType()
        {
                return $this->_adapter->getDialectType();
        }

        public function getInternalHandler()
        {
                return $this->_adapter->getInternalHandler();
        }

        public function getNestedTransactionSavepointName()
        {
                return $this->_adapter->getNestedTransactionSavepointName();
        }

        public function getRealSQLStatement()
        {
                return $this->_adapter->getRealSQLStatement();
        }

        public function getSQLBindTypes()
        {
                return $this->_adapter->getSQLBindTypes();
        }

        public function getSQLStatement()
        {
                return $this->_adapter->getSQLStatement();
        }

        public function getSQLVariables()
        {
                return $this->_adapter->getSQLVariables();
        }

        public function getType()
        {
                return $this->_adapter->getType();
        }

        public function isNestedTransactionsWithSavepoints()
        {
                return $this->_adapter->isNestedTransactionsWithSavepoints();
        }

        public function isUnderTransaction()
        {
                return $this->_adapter->isUnderTransaction();
        }

        public function lastInsertId($sequenceName = null)
        {
                return $this->_adapter->lastInsertId($sequenceName);
        }

        public function limit($sqlQuery, $number)
        {
                return $this->_adapter->limit($sqlQuery, $number);
        }

        public function listTables($schemaName = null)
        {
                return $this->_adapter->listTables($schemaName);
        }

        public function listViews($schemaName = null)
        {
                return $this->_adapter->listViews($schemaName);
        }

        public function modifyColumn($tableName, $schemaName, ColumnInterface $column, ColumnInterface $currentColumn = null)
        {
                return $this->_adapter->modifyColumn($tableName, $schemaName, $column, $currentColumn);
        }

        public function releaseSavepoint($name)
        {
                return $this->_adapter->releaseSavepoint($name);
        }

        public function rollback($nesting = null)
        {
                return $this->_adapter->rollback($nesting);
        }

        public function rollbackSavepoint($name)
        {
                return $this->_adapter->rollbackSavepoint($name);
        }

        public function setNestedTransactionsWithSavepoints($nestedTransactionsWithSavepoints)
        {
                return $this->_adapter->setNestedTransactionsWithSavepoints($nestedTransactionsWithSavepoints);
        }

        public function sharedLock($sqlQuery)
        {
                return $this->_adapter->sharedLock($sqlQuery);
        }

        public function supportSequences()
        {
                return $this->_adapter->supportSequences();
        }

        public function tableExists($tableName, $schemaName = null)
        {
                return $this->_adapter->tableExists($tableName, $schemaName);
        }

        public function tableOptions($tableName, $schemaName = null)
        {
                return $this->_adapter->tableOptions($tableName, $schemaName);
        }

        public function useExplicitIdValue()
        {
                return $this->_adapter->useExplicitIdValue();
        }

        public function viewExists($viewName, $schemaName = null)
        {
                return $this->_adapter->viewExists($viewName, $schemaName);
        }

}
