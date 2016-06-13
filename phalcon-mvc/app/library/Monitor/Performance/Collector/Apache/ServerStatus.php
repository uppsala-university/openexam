<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServerStatus.php
// Created: 2016-05-29 18:28:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector\Apache;

use OpenExam\Library\Monitor\Exception;

/**
 * Server status.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ServerStatus
{

        /**
         * Get server status from localhost.
         */
        const TARGET = "http://127.0.0.1/server-status?auto";
        /**
         * Load during 1 minute.
         */
        const LOAD_1 = 'Load1';
        /**
         * Load during 5 minutes.
         */
        const LOAD_5 = 'Load5';
        /**
         * Load during 15 minutes.
         */
        const LOAD_15 = 'Load15';
        /**
         * Total number of accesses.
         */
        const TOTAL_ACCESSES = 'Total Accesses';
        /**
         * Total number of bytes (kB).
         */
        const TOTAL_KBYTES = 'Total kBytes';
        /**
         * Accumulated user CPU.
         */
        const CPU_USER = 'CPUUser';
        /**
         * Accumulated system CPU.
         */
        const CPU_SYSTEM = 'CPUSystem';
        /**
         * CPU load.
         */
        const CPU_LOAD = 'CPULoad';
        /**
         * Requests per second.
         */
        const REQ_PER_SEC = 'ReqPerSec';
        /**
         * Bytes transfered per second.
         */
        const BYTES_PER_SEC = 'BytesPerSec';
        /**
         * Bytes per request.
         */
        const BYTES_PER_REQ = 'BytesPerReq';
        /**
         * Busy workers.
         */
        const BUSY_WORKERS = 'BusyWorkers';
        /**
         * Idle workers.
         */
        const IDLE_WORKERS = 'IdleWorkers';

        /**
         * Get Apache server status.
         * 
         * This data provider requires that mod_status is enabled in the
         * Apache configuration. It should also be accessable from localhost.
         * 
         * The data is requested using curl, so that extension has to be 
         * enabled.
         * 
         * @return array
         */
        public function getStatus()
        {
                $response = self::getResponse();

                $result = array(
                        'load'    => array(),
                        'total'   => array(),
                        'cpu'     => array(),
                        'request' => array(),
                        'workers' => array()
                );

                $lines = explode("\n", $response);
                foreach ($lines as $line) {
                        if (!strstr($line, ":")) {
                                continue;
                        }

                        list($key, $val) = explode(":", $line);

                        switch ($key) {
                                // 
                                // Avarage load last 1, 5 and 15 minutes:
                                // 
                                case self::LOAD_1:
                                        $result['load']['1min'] = trim($val);
                                        break;
                                case self::LOAD_15:
                                        $result['load']['15min'] = trim($val);
                                        break;
                                case self::LOAD_5:
                                        $result['load']['5min'] = trim($val);
                                        break;
                                // 
                                // Current and accumulated CPU time:
                                //
                                case self::CPU_LOAD:
                                        $result['cpu']['load'] = trim($val);
                                        break;
                                case self::CPU_SYSTEM:
                                        $result['cpu']['system'] = trim($val);
                                        break;
                                case self::CPU_USER:
                                        $result['cpu']['user'] = trim($val);
                                        break;
                                // 
                                // Requests:
                                // 
                                case self::BYTES_PER_REQ:
                                        $result['request']['bytes-req'] = trim($val);
                                        break;
                                case self::BYTES_PER_SEC:
                                        $result['request']['bytes-sec'] = trim($val);
                                        break;
                                case self::REQ_PER_SEC:
                                        $result['request']['num-sec'] = trim($val);
                                        break;
                                // 
                                // Total:
                                // 
                                case self::TOTAL_ACCESSES:
                                        $result['total']['access'] = trim($val);
                                        break;
                                case self::TOTAL_KBYTES:
                                        $result['total']['kbytes'] = trim($val);
                                        break;
                                // 
                                // Workers:
                                // 
                                case self::BUSY_WORKERS:
                                        $result['workers']['busy'] = trim($val);
                                        break;
                                case self::IDLE_WORKERS:
                                        $result['workers']['idle'] = trim($val);
                                        break;
                        }
                }

                return $result;
        }

        private static function getResponse()
        {
                if (!extension_loaded('curl')) {
                        throw new Exception("The cURL extension is not loaded");
                }

                if (!($curl = curl_init(self::TARGET))) {
                        throw new Exception("Failed initialize cURL");
                }

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                if (!$response = curl_exec($curl)) {
                        throw new Exception(sprintf("Failed request using cURL (%s)", curl_error($curl)));
                }

                curl_close($curl);
                return $response;
        }

}
