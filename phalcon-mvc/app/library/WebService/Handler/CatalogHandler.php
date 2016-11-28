<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CatalogHandler.php
// Created: 2015-04-03 14:31:38
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Handler;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Security\User;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;

/**
 * Catalog service handler.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CatalogHandler extends ServiceHandler
{

        /**
         * The catalog service.
         * @var DirectoryManager
         */
        private $_catalog;

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
         * Constructor.
         * @param ServiceRequest $request The service request.
         * @param User $user The logged in user.
         * @param string $action The dispatched action.
         * @param DirectoryManager $catalog The catalog service.
         */
        public function __construct($request, $user, $catalog)
        {
                $this->_catalog = $catalog;
                parent::__construct($request, $user);
        }

        /**
         * List all domains.
         * @return ServiceResponse 
         */
        public function getDomains()
        {
                $this->formatRequest("domains");
                
                $result = $this->_catalog->getDomains();
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Get name from user principal.
         * @return ServiceResponse 
         */
        public function getName()
        {
                $this->formatRequest("name");
                
                $result = $this->_catalog->getAttributeName($this->_request->data['principal']);
                $result = $this->formatResult($result);
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Get mail address from user principal.
         * @return ServiceResponse 
         */
        public function getMail()
        {
                $this->formatRequest("mail");
                
                $result = $this->_catalog->getAttributeMail($this->_request->data['principal']);
                $result = $this->formatResult($result);
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Get attribute from user principal.
         * @return ServiceResponse 
         */
        public function getAttribute()
        {
                $this->formatRequest("attribute");
                
                $result = $this->_catalog->getAttribute($this->_request->data['principal'], $this->_request->data['attribute']);
                $result = $this->formatResult($result);
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Get user principal groups.
         * @param string $method The request method (GET or POST).
         * @param string $principal The user principal.
         * @param string $output The output format.
         * @return ServiceResponse 
         */
        public function getGroups($method = "POST", $principal = null, $output = null)
        {
                if ($method == "GET") {
                        $this->_request->data['principal'] = $principal;
                        $this->_request->params['output'] = $output;
                }

                $this->formatRequest("groups");
                
                $result = $this->_catalog->getGroups($this->_request->data['principal'], $this->_request->data['attributes']);
                $result = $this->formatResult($result);
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Get group members.
         * @param string $method The request method (GET or POST).
         * @param string $group The group name.
         * @param string $output The output format.
         * @return ServiceResponse 
         */
        public function getMembers($method = "POST", $group = null, $output = null)
        {
                if ($method == "GET") {
                        $this->_request->data['group'] = $group;
                        $this->_request->params['output'] = $output;
                }

                $this->formatRequest("members");
                
                $result = $this->_catalog->getMembers($this->_request->data['group'], $this->_request->data['domain'], $this->_request->data['attributes']);
                $result = $this->formatResult($result);
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Search for user principals.
         * @return ServiceResponse 
         */
        public function findPrincipals()
        {
                $this->formatRequest("principal");

                $result = $this->_catalog->getPrincipal(current($this->_request->data), key($this->_request->data), $this->_request->params);
                $result = $this->formatResult($result);
                return new ServiceResponse($this, self::SUCCESS, $result);
        }

        /**
         * Format output based on request params preferences.
         * @param array $input The result data.
         * @param ServiceRequest $request The service request.
         * @return array
         */
        private function formatResult($input)
        {
                $output = $this->_request->params['output'];
                $svcref = $this->_request->params['svcref'];

                // 
                // Strip service references if requested:
                // 
                if ($svcref == false) {
                        for ($i = 0; $i < count($input); $i++) {
                                if ($input[$i] instanceof Principal) {
                                        unset($input[$i]->attr['svc']);
                                } else {
                                        unset($input[$i]['svc']);
                                }
                        }
                }

                // 
                // Convert single index array to strings:
                // 
                if (is_array(current($input))) {
                        for ($i = 0; $i < count($input); $i++) {
                                foreach ($input[$i] as $key => $val) {
                                        if (is_array($val) && count($val) == 1) {
                                                $input[$i][$key] = $input[$i][$key][0];
                                        }
                                }
                        }
                }

                // 
                // Format output:
                // 
                if ($output == self::OUTPUT_OBJECT) {
                        return $input;
                } elseif ($output == self::OUTPUT_STRIP) {
                        for ($i = 0; $i < count($input); $i++) {
                                $input[$i] = array_filter((array) $input[$i]);
                        }
                        return $input;
                } elseif ($output == self::OUTPUT_COMPACT) {
                        $result = array();
                        for ($i = 0; $i < count($input); $i++) {
                                $result = array_merge($result, array_values(array_filter((array) $input[$i])));
                        }
                        return $result;
                } elseif ($output == self::OUTPUT_ARRAY) {
                        for ($i = 0; $i < count($input); $i++) {
                                $input[$i] = array_values(array_filter((array) $input[$i]));
                        }
                        return $input;
                }
        }

        private function formatRequest($action)
        {
                if ($action == "groups") {
                        if (!isset($this->_request->data['attributes'])) {
                                $this->_request->data['attributes'] = array(Principal::ATTR_NAME);
                        }
                }

                if ($action == "members") {
                        if (!isset($this->_request->data['attributes'])) {
                                $this->_request->data['attributes'] = DirectoryManager::$DEFAULT_ATTR;
                        }
                        if (!isset($this->_request->data['domain'])) {
                                $this->_request->data['domain'] = null;
                        }
                }

                if ($action == "attribute" ||
                    $action == "name" ||
                    $action == "mail") {
                        if (!isset($this->_request->params['svcref'])) {
                                $this->_request->params['svcref'] = false;
                        }
                        if (!isset($this->_request->params['output'])) {
                                $this->_request->params['output'] = self::OUTPUT_COMPACT;
                        }
                }

                if (!isset($this->_request->data['principal'])) {
                        $this->_request->data['principal'] = $this->_user->getPrincipalName();
                }
                if (!isset($this->_request->params['output'])) {
                        $this->_request->params['output'] = self::OUTPUT_OBJECT;
                }
                if (!isset($this->_request->params['svcref'])) {
                        $this->_request->params['svcref'] = true;
                }
        }

}
