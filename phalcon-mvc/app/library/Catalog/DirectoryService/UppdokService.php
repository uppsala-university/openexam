<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UppdokService.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService;

use OpenExam\Library\Catalog\DirectoryService\Uppdok\Connection;
use OpenExam\Library\Catalog\DirectoryService\Uppdok\Data;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Catalog\ServiceAdapter;
use OpenExam\Library\Catalog\ServiceConnection;

/**
 * UPPDOK directory service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class UppdokService extends ServiceAdapter
{

        /**
         * The UPPDOK data service.
         * @var Data 
         */
        private $_data;
        /**
         * The service connection.
         * @var Connection 
         */
        private $_conn;

        /**
         * Constructor.
         * @param Connection $connection The UPPDOK service connection.
         */
        public function __construct($connection)
        {
                $this->_conn = $connection;

                $this->_data = new Data();
                $this->_data->setConnection($connection);
                $this->_data->setCompactMode(false);
                $this->_type = 'uppdok';
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                parent::__destruct();
                unset($this->_data);
        }

        /**
         * Set compact output mode.
         * @param bool $enable Enabled if true.
         */
        public function setCompactMode($enable = true)
        {
                $this->_data->setCompactMode($enable);
        }

        /**
         * Get members of group.
         * 
         * @param string $group The group name.
         * @param string $domain Restrict search to domain (optional).
         * @param array $attributes The attributes to return (optional).
         * @return Principal[]
         */
        public function getMembers($group, $domain = null, $attributes = null)
        {
                // 
                // Return entry from cache if existing:
                // 
                if ($this->_lifetime) {
                        $cachekey = sprintf("catalog-%s-members-%s", $this->_name, md5(serialize(array($group, $domain, $attributes))));
                        if ($this->cache->exists($cachekey, $this->_lifetime)) {
                                return $this->cache->get($cachekey, $this->_lifetime);
                        }
                }

                $result = array();
                $group = trim($group, '*');

                foreach ($this->_data->members($group) as $member) {
                        $principal = $member->getPrincipals($domain, $attributes);
                        $principal->attr = array(
                                'svc' => array(
                                        'name' => $this->_name,
                                        'type' => $this->_type,
                                        'ref'  => array(
                                                'group'    => $group,
                                                'year'     => $this->_data->getYear(),
                                                'semester' => $this->_data->getSemester()
                                        )
                                )
                        );
                        $result[] = $principal;
                }

                if (isset($cachekey)) {
                        return $this->setCacheData($cachekey, $result);
                } else {
                        return $result;
                }
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return $this->_conn;
        }

        /**
         * Get data service.
         * @return Data
         */
        public function getMemberService()
        {
                return $this->_data;
        }

}
