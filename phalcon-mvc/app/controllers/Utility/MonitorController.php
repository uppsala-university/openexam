<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    MonitorController.php
// Created: 2016-04-19 01:55:38
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Utility;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Monitor\Diagnostics;
use OpenExam\Library\Monitor\Exception;
use OpenExam\Library\Monitor\Performance;

/**
 * System monitor controller.
 * 
 * This controller provides access to system diagnostics and performance
 * monitoring. The data is sent in JSON encoded format,
 * 
 * Get performance counter data (examples):
 * -------------------------------------------
 * curl -XGET ${BASEURL}/utility/monitor/counters
 * curl -XGET ${BASEURL}/utility/monitor/counter/server?limit=20&keys=1
 * curl -XGET ${BASEURL}/utility/monitor/counter/server?limit=1
 * curl -XGET ${BASEURL}/utility/monitor/counter/server/cpu?limit=1
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class MonitorController extends GuiController
{

        /**
         * Send details only on failures.
         */
        const HEALTH_DYNAMIC = 0;
        /**
         * Boolean (success or failure).
         */
        const HEALTH_LEVEL_MIN = 1;
        /**
         * Health status be test (online and working).
         */
        const HEALTH_LEVEL_TEST = 2;
        /**
         * Health status by service.
         */
        const HEALTH_LEVEL_SERVICE = 3;
        /**
         * Allways send all details.
         */
        const HEALTH_FULL_STATUS = 4;
        /**
         * Number of seconds to keep health check data in cache.
         */
        const HEALTH_CHECK_LIFETIME = 30;

        public function initialize()
        {
                parent::initialize();
                $this->view->setTemplateBefore('cardbox');
        }

        public function indexAction()
        {
                
        }

        public function performanceAction()
        {
                if ($this->user->affiliation->isEmployee() == false) {
                        throw new SecurityException("Only available for employees", Error::FORBIDDEN);
                }
        }

        public function diagnosticsAction()
        {
                
        }

        /**
         * Send enabled performance counter list.
         */
        public function countersAction()
        {
                $content = array();

                $performance = new Performance();

                foreach ($performance->getCounters() as $counter) {
                        $type = $counter->getType();
                        $name = $counter->getName();
                        $desc = $counter->getDescription();

                        $content[$type] = array(
                                'name' => $name,
                                'desc' => $desc
                        );
                }

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send performance counter data and/or keys.
         * 
         * This action accepts column filters (source, time, host, addr and
         * milestone). The number of returned records can be limited by passing
         * limit=num.
         * 
         * By default, performance data is matched against this server. This
         * can be overridden by passing addr=* to get performance data for all
         * servers.
         * 
         * Three special request parameters can be passed: data=bool, keys=bool
         * and/or name=bool.
         * 
         * @param string $type The counter type.
         * @param string $subtype The counter sub type (e.g. cpu).
         */
        public function counterAction($type, $subtype = null)
        {
                if ($this->user->affiliation->isEmployee() == false) {
                        throw new SecurityException("Only available for employees", Error::FORBIDDEN);
                }

                $performance = new Performance();

                if ($this->request->has('limit')) {
                        $performance->setLimit($this->request->get('limit', 'int'));
                }
                if ($this->request->has('filter')) {
                        $performance->setFilter($this->request->get('filter', 'array'));
                }
                if ($this->request->has('source') && $this->request->get('source')) {
                        $performance->addFilter('source', $this->request->get('source', 'string'));
                }
                if ($this->request->has('time')) {
                        $performance->addFilter('time', $this->request->get('time', 'string'));
                }
                if ($this->request->has('host')) {
                        $performance->addFilter('host', $this->request->get('host', 'string'));
                }
                if ($this->request->has('addr')) {
                        $performance->addFilter('addr', $this->request->get('addr', 'string'));
                }
                if ($this->request->has('milestone') && $this->request->get('milestone')) {
                        $performance->addFilter('milestone', $this->request->get('milestone', 'string'));
                }

                if (!($counter = $performance->getCounter($type))) {
                        return false;
                }

                $content = array(
                        'keys' => false,
                        'data' => true,
                        'name' => false
                );

                if ($this->request->has('keys') && $this->request->get('keys', 'int') == 1) {
                        $content['keys'] = true;
                }
                if ($this->request->has('data') && $this->request->get('data', 'int') == 0) {
                        $content['data'] = false;
                }
                if ($this->request->has('name')) {
                        $content['name'] = true;
                }
                if ($content['name'] && !$counter->hasSource()) {
                        $content['name'] = false;
                }

                if ($content['keys']) {
                        $content['keys'] = $counter->getKeys();
                }
                if ($content['data']) {
                        $content['data'] = $counter->getData();
                }
                if ($content['name']) {
                        $content['name'] = $counter->getSources();
                }

                if (isset($subtype)) {
                        // 
                        // Get sub counter and dynamic modify the counter data filter using its
                        // implicit performance object reference.
                        // 
                        $content['data'] = array();

                        foreach ($counter->getAddresses() as $from) {
                                $performance->addFilter('addr', $from['addr']);
                                $content['data'] = array_merge(
                                    $counter->getCounter($subtype)->getData(), $content['data']
                                );
                        }
                }

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send system health status.
         */
        public function healthAction($details = self::HEALTH_DYNAMIC)
        {
                if ($this->request->has('details')) {
                        $details = $this->request->get('details', 'int');
                }
                if ($details < 0 || $details > self::HEALTH_FULL_STATUS) {
                        throw new Exception("Invalid details level $details requested", Error::NOT_ACCEPTABLE);
                }

                $content = $this->getHealth($details);

                $this->view->disable();
                $this->response->setJsonContent(array('status' => $content));
                $this->response->send();
        }

        /**
         * Get system health status.
         * 
         * @param int $details The details level.
         * @return array
         */
        private function getHealth($details)
        {
                $cachekey = sprintf("health-level-%d", $details);
                $lifetime = self::HEALTH_CHECK_LIFETIME;

                if ($this->cache->exists($cachekey, $lifetime)) {
                        return $this->cache->get($cachekey, $lifetime);
                }

                $diag = new Diagnostics();

                $status = array('status' => array());

                $status['online'] = $diag->isOnline();
                $status['status']['online'] = $diag->hasFailed() == false;

                $status['working'] = $diag->isWorking();
                $status['status']['working'] = $diag->hasFailed() == false;

                $status['result'] = $diag->getResult();

                if ($details == self::HEALTH_DYNAMIC) {
                        if ($status['status']['online'] && $status['status']['working']) {
                                $content = true;
                        } else {
                                $content = $status['result'];
                        }
                } elseif ($details == self::HEALTH_LEVEL_MIN) {
                        $content = $status['status']['online'] && $status['status']['working'];
                } elseif ($details == self::HEALTH_LEVEL_TEST) {
                        $content = $status['status'];
                } elseif ($details == self::HEALTH_LEVEL_SERVICE) {
                        $content = array(
                                'online'  => $status['online'],
                                'working' => $status['working']
                        );
                } elseif ($details == self::HEALTH_FULL_STATUS) {
                        $content = $status['result'];
                }

                $this->cache->save($cachekey, $content, $lifetime);

                return $content;
        }

}
