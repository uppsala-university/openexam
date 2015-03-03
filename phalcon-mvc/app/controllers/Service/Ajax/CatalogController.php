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

use OpenExam\Controllers\Service\AjaxController;
use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\Principal;

/**
 * AJAX controller for catalog (directory information) service.
 * 
 * All query handlers operating on an user principal name can be called without
 * the principal argument. If principal is missing, then the logged on user
 * principal is used instead.
 * 
 * To get the department of calling user, just append the required attribute:
 * 
 * input: '{"attribute":"department"}'  // Use logged on user principal name.
 * 
 * Query attributes (/ajax/catalog/attribut):
 * ---------------------------------------------
 * 
 * Get attributes (like email addresses) for user principal.
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
 * Get members of group.
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
 * Query principals (/ajax/catalog/principal):
 * ---------------------------------------------
 * 
 * Get user principal objects. This is a pure search action.
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
 * Reading groups (/ajax/catalog/groups):
 * ---------------------------------------------
 * 
 * Get groups that the user principal is a member of.
 * 
 * // The default is to only list group names:
 * input: '{"principal":"user@example.com"}'
 * 
 * // Select attriubutes to include:
 * input: '{"principal":"user@example.com","attributes":["cn","name","gidnumber","distinguishedName","description"]}'
 * 
 * // Format output. Possible arguments are strip, object, array or compact:
 * '{"data":{"principal":"user@example.com"},"params":{"output":"array"}}'
 * 
 * // All attributes including nested groups etc. Will fail if attributes
 * // contains binary data:
 * input: '{"principal":"user@example.com","attributes":["*"]}'
 * 
 * Output formatting:
 * ---------------------------------------------
 * 
 * Most actions supports output filtering. The output format is submitted in
 * params (for POST). The members and groups action also supports GET request,
 * and the output format is then provided after the subject:
 * 
 * <code>
 * curl -XGET ${BASEURL}/ajax/catalog/groups/user@example.com/array
 * </code>
 * 
 * The possible output formatters are: object (default), array, compact
 * and strip.
 * 
 * o) Some examples:
 * 
 * // Get member of group formatted as array:
 * input: '{"data":{"group":"3FV271"},"params":{"output":"array"}}'
 * 
 * // User principals in stripped format:
 * input: '{"data":{"uid":"test*"},"params":{"output":"strip"}}'
 * 
 * o) Directory entity references:
 * 
 * By default, the result contains directory entity references to support
 * browsing. If browsing is not required, then the service reference can 
 * be stripped by passing svcref == false:
 * 
 * input: '{"data":{...},"params":{"svcref":false}}'
 * 
 * Combining output formatting with svcref == false can be used to return
 * plain values or simple key/value arrays:
 * 
 * // Return key/value array of first 10 people named Anders:
 * input: '{"data":{"gn":"Anders"},"params":{"attr":["name"],"output":"strip","svcref":false,"limit":10}}'
 * 
 * // Same as above, but formatted as plain value array:
 * input: '{"data":{"gn":"Anders"},"params":{"attr":["name"],"output":"compact","svcref":false,"limit":10}}'
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CatalogController extends AjaxController
{

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
         * The query data.
         * @var array 
         */
        private $data;
        /**
         * The query parameters.
         * @var array 
         */
        private $params;

        public function initialize()
        {
                parent::initialize();

                if ($this->request->isPost()) {
                        $request = $this->getRequest();
                }

                if ($this->dispatcher->getActionName() == "groups") {
                        if (!isset($request->data['attributes'])) {
                                $request->data['attributes'] = array(Principal::ATTR_NAME);
                        }
                }

                if ($this->dispatcher->getActionName() == "members") {
                        if (!isset($request->data['attributes'])) {
                                $request->data['attributes'] = DirectoryManager::$DEFAULT_ATTR;
                        }
                        if (!isset($request->data['domain'])) {
                                $request->data['domain'] = null;
                        }
                }

                if ($this->dispatcher->getActionName() == "attribute" ||
                    $this->dispatcher->getActionName() == "name" ||
                    $this->dispatcher->getActionName() == "mail") {
                        if (!isset($request->params['svcref'])) {
                                $request->params['svcref'] = false;
                        }
                        if (!isset($request->params['output'])) {
                                $request->params['output'] = self::OUTPUT_COMPACT;
                        }
                }

                if (!isset($request->data['principal'])) {
                        $request->data['principal'] = $this->user->getPrincipalName();
                }
                if (!isset($request->params['output'])) {
                        $request->params['output'] = self::OUTPUT_OBJECT;
                }
                if (!isset($request->params['svcref'])) {
                        $request->params['svcref'] = true;
                }

                $this->data = $request->data;
                $this->params = $request->params;
        }

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

        /**
         * List all domains.
         */
        public function domainsAction()
        {
                $this->sendResponse(self::SUCCESS, $this->catalog->getDomains());
        }

        /**
         * Get name from user principal.
         */
        public function nameAction()
        {
                $result = $this->catalog->getName($this->data['principal']);
                $result = $this->formatResult($result, $this->params['output']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Get mail address from user principal.
         */
        public function mailAction()
        {
                $result = $this->catalog->getMail($this->data['principal']);
                $result = $this->formatResult($result, $this->params['output']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Get attribute from user principal.
         */
        public function attributeAction()
        {
                $result = $this->catalog->getAttribute($this->data['principal'], $this->data['attribute']);
                $result = $this->formatResult($result, $this->params['output']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Get user principal groups (GET and POST).
         * @param string $principal The user principal.
         * @param string $output The output format.
         */
        public function groupsAction($principal = null, $output = null)
        {
                if ($this->request->isGet()) {
                        $this->data['principal'] = $principal;
                        $this->params['output'] = $output;
                }

                $result = $this->catalog->getGroups($this->data['principal'], $this->data['attributes']);
                $result = $this->formatResult($result, $this->params['output']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Get group members (GET and POST).
         * @param string $group The group name.
         * @param string $output The output format.
         */
        public function membersAction($group = null, $output = null)
        {
                if ($this->request->isGet()) {
                        $this->data['group'] = $group;
                        $this->params['output'] = $output;
                }

                $result = $this->catalog->getMembers($this->data['group'], $this->data['domain'], $this->data['attributes']);
                $result = $this->formatResult($result, $this->params['output']);
                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Search for user principals.
         */
        public function principalAction()
        {
                $result = $this->catalog->getPrincipal(current($this->data), key($this->data), $this->params);
                $result = $this->formatResult($result, $this->params['output']);

                $this->sendResponse(self::SUCCESS, $result);
        }

        /**
         * Format output based on request params preferences.
         * @param array $input The result data.
         * @return array
         */
        private function formatResult($input)
        {
                $output = $this->params['output'];
                $svcref = $this->params['svcref'];

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

}
