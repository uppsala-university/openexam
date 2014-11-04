<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CatalogController.php
// Created: 2014-10-31 10:38:09
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service\Ajax;

use OpenExam\Controllers\ServiceController;
use OpenExam\Library\Catalog\DirectoryManager;

/**
 * AJAX controller for catalog (directory information) service.
 * 
 * Query attributes (/ajax/catalog/attribut):
 * ---------------------------------------------
 * 
 * // Get mail attribute(s) using default domain:
 * input: '{"principal":"user","attribute":"mail"}'
 * 
 * // Get mail attribute(s) using principal name:
 * input: '{"principal":"user@example.com","attribute":"mail"}'
 * 
 * // Get all attributes:
 * input: '{"principal":"user@example.com","attribute":"*"}'
 * 
 * // Get custom attributes (service dependant) -> ["employee","member","staff"]:
 * input: '{"principal":"user@example.com","attribute":"edupersonaffiliation"}'
 * 
 * Query members (/ajax/catalog/members):
 * ---------------------------------------------
 * 
 * // Get all members in group 3FV271, looking in all domains:
 * input: '{"group":"3FV271"}'
 * 
 * // Get all members in group 3FV271, looking in domain example.com:
 * input: '{"group":"3FV271","domain":"example.com"}'
 * 
 * // Filter returned attriubutes (depends on directory service backend):
 * input: '{"group":"3FV271","attributes":["principal","cn","mail"]}'
 * 
 * // Format output. Possible arguments are strip, object, array or compact:
 * input: '{"data":{"group":"3FV271"},"params":{"output":"array"}}'
 * 
 * Query principals (/ajax/catalog/principal):
 * ---------------------------------------------
 * 
 * // Get first five user principals with given name equals to Anders:
 * input: '{"data":{"gn":"Anders"},"params":{"attr":["principal","mail","uid"],"domain":"example.com","limit":5}}'
 * 
 * // Get complete principal objects:
 * input: '{"data":{"uid":"test*"},"params":{"attr":["*"],"domain":"example.com","data":true}}'
 * 
 * // Get complete principal objects (better):
 * input: '{"data":{"uid":"test*"},"params":{"attr":["principal","uid","cn","sn","gn","pnr","mail"]}}'
 * 
 * // Get complete principal objects including extended data:
 * input: '{"data":{"uid":"test*"},"params":{"attr":["*"],"domain":"example.com","data":true}}'
 * 
 * // Format output. Possible arguments are strip, object, array or compact:
 * input: '{"data":{"uid":"test*"},"params":{"output":"strip"}}'
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CatalogController extends ServiceController
{

        /**
         * Success response tag.
         */
        const SUCCESS = 'success';
        /**
         * failure response tag.
         */
        const FAILURE = 'failed';
        /**
         * Output as obejct.
         */
        const OUTPUT_OBJECT = 'object';
        /**
         * Output as array.
         */
        const OUTPUT_ARRAY = 'array';
        /**
         * Strip null values in output.
         */
        const OUTPUT_STRIP = 'strip';
        /**
         * Compact otuput format.
         */
        const OUTPUT_COMPACT = 'compact';

        /**
         * Display documentation of the AJAX service API.
         */
        public function apiAction()
        {
                // TODO: use view for displaying API docs

                $content = array(
                        "usage"   => array(
                                "/ajax/catalog/{target}"           => "POST",
                                "/ajax/catalog/{target}/{subject}" => "GET",
                        ),
                        "example" => array(
                                "/ajax/catalog/attribute"       => "Query user attributes (POST)",
                                "/ajax/catalog/groups"          => "Get user groups (POST)",
                                "/ajax/catalog/groups/{user}"   => "Browse user groups (GET)",
                                "/ajax/catalog/members"         => "Get group members (POST)",
                                "/ajax/catalog/members/{group}" => "Browse group members (GET)",
                                "/ajax/catalog/principal"       => "Get user principal object(s) (POST)",
                                "/ajax/catalog/domains"         => "Get all domains (GET)",
                                "/ajax/catalog/name"            => "Query user principal names (POST)",
                                "/ajax/catalog/mail"            => "Query user principal email addresses (POST)",
                        )
                );

                $this->response->setJsonContent($content);
                $this->response->send();
        }

        public function indexAction()
        {
                
        }

        public function domainsAction()
        {
                $this->sendResponse(self::SUCCESS, $this->catalog->getDomains());
        }

        public function nameAction()
        {
                list($data, $params) = $this->getInput();
                $result = $this->catalog->getName($data['principal']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        public function mailAction()
        {
                list($data, $params) = $this->getInput();
                $result = $this->catalog->getMail($data['principal']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        public function attributeAction()
        {
                list($data, $params) = $this->getInput();
                $result = $this->catalog->getAttribute($data['principal'], $data['attribute']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        public function membersAction($group = null, $output = null)
        {
                if ($this->request->isPost()) {
                        list($data, $params) = $this->getInput();
                }
                if ($this->request->isGet()) {
                        $data['group'] = $group;
                        $params['output'] = $output;
                }

                if (!isset($data['domain'])) {
                        $data['domain'] = null;
                }
                if (!isset($data['attributes'])) {
                        $data['attributes'] = DirectoryManager::$DEFAULT_ATTR;
                }
                if (!isset($params['output'])) {
                        $params['output'] = self::OUTPUT_STRIP;
                }

                $result = $this->catalog->getMembers($data['group'], $data['domain'], $data['attributes']);
                $result = $this->formatResult($result, $params['output']);

                $this->sendResponse(self::SUCCESS, $result);
        }

        public function principalAction()
        {
                list($data, $params) = $this->getInput();

                if (!isset($params['output'])) {
                        $params['output'] = self::OUTPUT_OBJECT;
                }

                $result = $this->catalog->getPrincipal(current($data), key($data), $params);
                $result = $this->formatResult($result, $params['output']);

                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Send result to peer.
         * @param string $status The status label.
         * @param mixed $result The operation result.
         */
        private function sendResponse($status, $result)
        {
                $this->response->setJsonContent(array($status => $result));
                $this->response->send();
        }

        private function formatResult($result, $output)
        {
                if ($output == self::OUTPUT_OBJECT) {
                        return $result;
                } elseif ($output == self::OUTPUT_STRIP) {
                        for ($i = 0; $i < count($result); $i++) {
                                $result[$i] = array_filter((array) $result[$i]);
                        }
                        return $result;
                } elseif ($output == self::OUTPUT_COMPACT) {
                        $output = array();
                        for ($i = 0; $i < count($result); $i++) {
                                $output = array_merge($output, array_values(array_filter((array) $result[$i])));
                        }
                        return $output;
                } elseif ($output == self::OUTPUT_ARRAY) {
                        for ($i = 0; $i < count($result); $i++) {
                                $result[$i] = array_values(array_filter((array) $result[$i]));
                        }
                        return $result;
                }
        }

}
