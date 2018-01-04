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
// File:    Connection.php
// Created: 2016-06-02 01:42:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service\Adapter\Uppdok;

use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Service\Connection as ServiceConnection;
use Phalcon\Mvc\User\Component;
use const INFO_CGI_DEBUG;
use const INFO_CGI_PORT;
use const INFO_CGI_SERVER;
use const INFO_CGI_VERBOSE;

if (!defined('INFO_CGI_SERVER')) {
        define('INFO_CGI_SERVER', 'localhost');
}
if (!defined('INFO_CGI_PORT')) {
        define('INFO_CGI_PORT', 108);
}
if (!defined('INFO_CGI_DEBUG')) {
        define('INFO_CGI_DEBUG', false);
}
if (!defined('INFO_CGI_VERBOSE')) {
        define('INFO_CGI_VERBOSE', false);
}

/**
 * The UPPDOK service connection.
 * 
 * Connection to UPPDOK (thru InfoCGI) is stateless, so we always return
 * true on open() and connected().
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Connection extends Component implements ServiceConnection
{

        /**
         * URL template for GET request.
         */
        const TARGET = "http://%s:%d/getreg?typ=kurs&kod=%s&termin=%d%d&from=%s&pass=%s";

        /**
         * The cURL handle.
         * @var resource 
         */
        private $_handle;
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
        public function __construct($user, $pass, $host = INFO_CGI_SERVER, $port = INFO_CGI_PORT)
        {
                if (!isset($user) || !isset($pass) || !isset($host)) {
                        throw new Exception("Missing username, password or server name.");
                }

                $this->_user = $user;
                $this->_pass = $pass;
                $this->_host = $host;
                $this->_port = $port;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_user);
                unset($this->_pass);
                unset($this->_host);
                unset($this->_port);
        }

        public function close()
        {
                curl_close($this->_handle);
        }

        public function connected()
        {
                return is_resource($this->_handle);
        }

        public function open()
        {
                if (!extension_loaded("curl")) {
                        throw new Exception("The curl extension is not loaded");
                }

                $this->_handle = curl_init();
                if (!isset($this->_handle)) {
                        throw new Exception("Failed initialize cURL");
                }


                if (INFO_CGI_DEBUG) {
                        curl_setopt($this->_handle, CURLOPT_HEADER, 1);
                }
                if (INFO_CGI_VERBOSE) {
                        curl_setopt($this->_handle, CURLOPT_VERBOSE, 1);
                }

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

        public function find($url)
        {
                $this->open();

                curl_setopt($this->_handle, CURLOPT_URL, $url);
                curl_setopt($this->_handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($this->_handle, CURLOPT_RETURNTRANSFER, true);

                $content = curl_exec($this->_handle);
                $error = curl_error($this->_handle);
                $info = curl_getinfo($this->_handle);

                if (!$content || $info['http_code'] != 200) {
                        $this->logger->system->error(sprintf("Failed fetch membership information from UPPDOK data: %s", $error));
                        throw new Exception($this->tr->_("There was a problem talking to the directory service, course information is unavailable due to network or configuration problems"));
                }

                $this->close();
                return $content;
        }

}
