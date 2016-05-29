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
 * Get performance counters:
 * ---------------------------
 * curl -XGET ${BASEURL}/utility/diagnostics/performance/server?limit=20&keys=1
 * curl -XGET ${BASEURL}/utility/diagnostics/performance/server?limit=1
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
         * To get initial data along with keys (labels for display), send:
         * /utility/diagnostics/performance/server?keys=1
         * 
         * To get sub sequent data with for updating graph, send:
         * /utility/diagnostics/performance/server?limit=1
         * 
         * @param string $type The performance counter (e.g. system).
         */
        public function performanceAction($type)
        {
                $diagnostics = new Diagnostics();
                $performance = $diagnostics->getPerformanceStatus();

                if ($this->request->has('limit')) {
                        $performance->setLimit($this->request->get('limit', 'int'));
                }
                if ($this->request->has('filter')) {
                        $performance->setFilter($this->request->get('filter', 'string'));
                }

                if ($this->request->has('source')) {
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
                        'data' => true
                );

                if ($this->request->has('keys') && $this->request->get('keys', 'int') == 1) {
                        $content['keys'] = true;
                }
                if ($this->request->has('data') && $this->request->get('data', 'int') == 0) {
                        $content['data'] = false;
                }

                if ($content['keys']) {
                        $content['keys'] = $counter->getKeys();
                }
                if ($content['data']) {
                        $content['data'] = $counter->getData();
                }

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

}
