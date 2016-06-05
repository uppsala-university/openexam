<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DiagnosticsController.php
// Created: 2016-04-19 01:55:38
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Utility;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Diagnostics;

/**
 * System diagnostics controller.
 * 
 * Get performance counters (examples):
 * ---------------------------------------
 * curl -XGET ${BASEURL}/utility/diagnostics/performance
 * curl -XGET ${BASEURL}/utility/diagnostics/performance/server?limit=20&keys=1
 * curl -XGET ${BASEURL}/utility/diagnostics/performance/server?limit=1
 * curl -XGET ${BASEURL}/utility/diagnostics/performance/server/cpu?limit=1
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DiagnosticsController extends GuiController
{

        public function indexAction()
        {
                
        }

        public function monitorAction()
        {
                
        }

        /**
         * Send performance data as JSON.
         * 
         * The performance counters are accessed in REST style:
         * 
         * <code>
         * /utility/diagnostics/performance             (GET - all counter)
         * /utility/diagnostics/performance/server      (GET - the server counter)
         * /utility/diagnostics/performance/server/cpu  (GET - the cpu sub counter)
         * </code>
         * 
         * Request parameters can be passed to filter on data fields and
         * pseudo matches like (name=bool, keys=bool, limit=num or data=bool).
         * 
         * Use this request to fetch keys for all enabled perfomance counters:
         * 
         * <code>
         * /utility/diagnostics/performance?keys=1
         * </code>
         * 
         * Use either key to request performance data. By default, this request 
         * is going to return data too (20 records by default) along with the
         * translated keys:
         * 
         * <code>
         * /utility/diagnostics/performance/server?keys=1
         * </code>
         * 
         * To get sub-sequent data for graph update, pass a limit on returned
         * records:
         * 
         * <code>
         * /utility/diagnostics/performance/server?limit=1
         * </code>
         * 
         * By default, only data for this server is returned. To get data
         * for all servers pass 'addr=*' as request param:
         * 
         * <code>
         * /utility/diagnostics/performance/server?limit=1&addr=*
         * </code>
         * 
         * Sub counters can also be queried. In this mode, data for all servers
         * are returned, while still applying any filtering (e.g. on date):
         * 
         * <code>
         * /utility/diagnostics/performance/server/cpu
         * </code>
         * 
         * @param string $type The performance counter (e.g. system).
         * @param string $subtype The counter sub type (e.g. cpu).
         */
        public function performanceAction($type = null, $subtype = null)
        {
                if (!isset($type)) {
                        $this->sendCounters();
                } else {
                        $this->sendCounterData($type, $subtype);
                }
        }

        /**
         * Send enabled performance counter list.
         */
        private function sendCounters()
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
         * @param string $type The counter type.
         * @param string $subtype The counter sub type (e.g. cpu).
         */
        private function sendCounterData($type, $subtype = null)
        {
                $diagnostics = new Diagnostics();
                $performance = $diagnostics->getPerformanceStatus();

                if ($this->request->has('limit')) {
                        $performance->setLimit($this->request->get('limit', 'int'));
                }
                if ($this->request->has('filter')) {
                        $performance->setFilter($this->request->get('filter', 'string'));
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
                        return;
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
