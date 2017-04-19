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
use OpenExam\Library\WebService\Handler\CatalogHandler;

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
 * // Get user affiliation of caller -> ["student"]:
 * input: '{"attribute":"affiliation"}'
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
 * // Filter returned attributes (depends on directory service backend):
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
 * // Get principal objects including the user affiliation:
 * input: '{"data":{"uid":"test*"},"params":{"attr":["principal","affiliation"]}}'
 * 
 * Reading groups (/ajax/catalog/groups):
 * ---------------------------------------------
 * 
 * Get groups that the user principal is a member of.
 * 
 * // The default is to only list group names:
 * input: '{"principal":"user@example.com"}'
 * 
 * // Select attributes to include:
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
         *
         * @var CatalogHandler 
         */
        private $_handler;

        /**
         * Constructor.
         */
        public function initialize()
        {
                parent::initialize();
                $this->_handler = new CatalogHandler($this->getRequest(), $this->user, $this->catalog);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_handler);
                parent::__destruct();
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
                $response = $this->_handler->getDomains();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Get name from user principal.
         */
        public function nameAction()
        {
                $response = $this->_handler->getName();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Get mail address from user principal.
         */
        public function mailAction()
        {
                $response = $this->_handler->getMail();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Get attribute from user principal.
         */
        public function attributeAction()
        {
                $response = $this->_handler->getAttribute();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Get user principal groups (GET and POST).
         * @param string $principal The user principal.
         * @param string $output The output format.
         */
        public function groupsAction($principal = null, $output = null)
        {
                $response = $this->_handler->getGroups($this->request->getMethod(), $principal, $output);
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Get group members (GET and POST).
         * @param string $group The group name.
         * @param string $output The output format.
         */
        public function membersAction($group = null, $output = null)
        {
                $response = $this->_handler->getMembers($this->request->getMethod(), $group, $output);
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Search for user principals.
         */
        public function principalAction()
        {
                $response = $this->_handler->findPrincipals();
                $this->sendResponse($response);
                unset($response);
        }

}
