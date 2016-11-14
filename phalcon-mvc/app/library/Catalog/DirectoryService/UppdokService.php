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

if (!defined('INFO_CGI_SERVER')) {
        define('INFO_CGI_SERVER', 'localhost');
}
if (!defined('INFO_CGI_PORT')) {
        define('INFO_CGI_PORT', 108);
}

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
        private $_uppdok;

        /**
         * Constructor.
         * @param string $user The service username.
         * @param string $pass The service password.
         * @param string $host The service hostname.
         * @param int $port The service port.
         */
        public function __construct($user, $pass, $host = INFO_CGI_SERVER, $port = INFO_CGI_PORT)
        {
                $this->_uppdok = new Data(
                    new Connection($user, $pass, $host, $port)
                );
                $this->_uppdok->setCompactMode(false);
                $this->_type = 'uppdok';
        }

        /**
         * Get members of group.
         * @param string $group The group name.
         * @param string $domain Restrict search to domain.
         * @param array $attributes The attributes to return.
         * @return Principal[]
         */
        public function getMembers($group, $domain, $attributes)
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

                foreach ($this->_uppdok->members($group) as $member) {
                        $principal = $member->getPrincipal($domain, $attributes);
                        $principal->attr = array(
                                'svc' => array(
                                        'name' => $this->_name,
                                        'type' => $this->_type,
                                        'ref'  => array(
                                                'group'    => $group,
                                                'year'     => $this->_uppdok->getYear(),
                                                'semester' => $this->_uppdok->getSemester()
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
                return $this->_uppdok->getConnection();
        }

}
