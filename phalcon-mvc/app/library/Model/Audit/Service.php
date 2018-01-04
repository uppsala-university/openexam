<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Service.php
// Created: 2016-04-21 04:04:50
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit;

use OpenExam\Library\Model\Audit\Config\AuditConfig;
use OpenExam\Library\Model\Audit\Config\ServiceConfig;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use PDO;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\User\Component;

/**
 * The model audit service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Service extends Component
{

        /**
         * The service config.
         * @var ServiceConfig 
         */
        private $_config;

        /**
         * Constructor.
         * @param ServiceConfig $config The audit service config.
         */
        public function __construct($config = null)
        {
                $this->_config = $config;

                if (!isset($this->_config)) {
                        $this->_config = new ServiceConfig();
                }
        }

        /**
         * Check if audit config exist for this model.
         * 
         * <code>
         * if ($this->audit->hasConfig('answer')) {
         *      // do something...
         * }
         * </code>
         * 
         * @param string $model The resource name.
         * @return boolean
         */
        public function hasConfig($model)
        {
                return $this->_config->hasAudit($model);
        }

        /**
         * Get audit config for this model.
         * 
         * @param string $model The audit config.
         * @return AuditConfig|boolean
         */
        public function getConfig($model)
        {
                if (($config = $this->_config->getConfig($model))) {
                        return new AuditConfig($config);
                } else {
                        return false;
                }
        }

        /**
         * Disable audit for this model.
         * @param string $model The resource name.
         */
        public function setDisabled($model)
        {
                $this->_config->setDisabled($model);
        }

        /**
         * Get revisions for this model object.
         * 
         * @param ModelInterface $model
         * @return array
         */
        public function getRevisions($model)
        {
                $name = $model->getResourceName();

                if (!($config = $this->getConfig($name))) {
                        return false;
                }
                if (!$config->hasTarget(Audit::TARGET_DATA)) {
                        return false;
                }
                if (!$config->hasAction(ObjectAccess::UPDATE)) {
                        return false;
                }

                $target = $config->getTarget(Audit::TARGET_DATA);

                $sql = sprintf("SELECT * FROM `%s` WHERE res = '%s' AND rid = %d AND type = 'update'", $target['table'], $name, $model->id);
                $dbh = $this->getDI()->get($target['connection']);
                $sth = $dbh->prepare($sql);
                $res = $sth->execute();

                $arr = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $arr;
        }

}
