<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    HandlerBase.php
// Created: 2014-08-25 00:04:21
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Handler;

use \OpenExam\Models\ModelBase;

/**
 * Base class for all model handlers.
 *
 * This class provides default behavours for CRUD-operations. Each CRUD-operation
 * is performed as the current role (as passed to constructor). Authorization
 * is done using callback to the ACL function allowed() provided by the security 
 * plugin.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class HandlerBase extends \Phalcon\DI\Injectable
{

        const create = "create";
        const read = "read";
        const update = "update";
        const delete = "delete";

        /**
         * Requested role.
         * @var string 
         */
        protected $role;

        public function __construct($role)
        {
                $this->role = $role;
                $this->setDI(\Phalcon\DI\FactoryDefault::getDefault());
        }

        /**
         * Authorize action on model thru ACL.
         * 
         * @param \OpenExam\Models\ModelBase $model
         * @param string $action
         * @throws \Phalcon\Security\Exception
         */
        protected function authorize($model, $action)
        {
                if ($this->security->allowed($this->role, $model->getName(), $action) == false) {
                        throw new \Phalcon\Security\Exception(_("You are not authorized to make this call."));
                }
        }

        public function create(Model $model)
        {
                $this->authorize($model, self::create);
                $model->create();
                return $model->id;
        }

        public function read($model)
        {
                $this->authorize($model, self::read);
                $class = get_class($model);
                return $class::findFirstById($model->id);
        }

        public function update(Model $model)
        {
                $this->authorize($model, self::update);
                $model->update();
        }

        public function delete(Model $model)
        {
                $this->authorize($model, self::delete);
                $model->delete();
        }

}
