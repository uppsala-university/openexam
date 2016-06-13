<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UppdokConnection.php
// Created: 2016-06-02 01:42:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService\Uppdok;

use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\ServiceConnection;

if (!defined('INFO_CGI_SERVER')) {
        define('INFO_CGI_SERVER', 'localhost');
}
if (!defined('INFO_CGI_PORT')) {
        define('INFO_CGI_PORT', 108);
}

/**
 * The UPPDOK service connection.
 * 
 * Connection to UPPDOK (thru InfoCGI) is stateless, so we always return
 * true on open() and connected().
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UppdokConnection implements ServiceConnection
{

        /**
         * The InfoCGI service username.
         * @var string 
         */
        private $_user;
        /**
         * The InfoCGI service password.
         * @var string 
         */
        private $_pass;
        /**
         * The InfoCGI service hostname.
         * @var string 
         */
        private $_host;
        /**
         * The InfoCGI service port.
         * @var int 
         */
        private $_port;

        /**
         * Constructor.
         * @param string $user The InfoCGI service username.
         * @param string $pass The InfoCGI service password.
         * @param string $host The InfoCGI service hostname.
         * @param int $port The InfoCGI service port.
         * @throws Exception
         */
        public function __construct($user, $pass, $host, $port)
        {
                if (!isset($user) || !isset($pass) || !isset($host)) {
                        throw new Exception("Missing username, password or server name.");
                }

                $this->_user = $user;
                $this->_pass = $pass;
                $this->_host = $host;
                $this->_port = $port;
        }

        public function close()
        {
                
        }

        public function connected()
        {
                return true;
        }

        public function open()
        {
                return true;
        }

        public function hostname()
        {
                return $this->_host;
        }

        public function port()
        {
                return $this->_port;
        }

        public function user()
        {
                return $this->_user;
        }

        public function pass()
        {
                return $this->_pass;
        }

}
