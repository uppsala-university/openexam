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
use OpenExam\Library\Core\Diagnostics;

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

        public function indexAction()
        {
                
        }

        public function performanceAction()
        {
                
        }

        /**
         * Send enabled performance counter list.
         */
        public function countersAction()
        {
                $diagnostics = new Diagnostics();
                $performance = $diagnostics->getPerformanceStatus();

                $content = array();

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
                $diagnostics = new Diagnostics();
                $performance = $diagnostics->getPerformanceStatus();

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
                if ($this->request->has('milestone')) {
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

}
