<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Service.php
// Created: 2016-04-21 04:04:50
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit;

use OpenExam\Library\Model\Audit\Config\AuditConfig;
use OpenExam\Library\Model\Audit\Config\ServiceConfig;
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

}
