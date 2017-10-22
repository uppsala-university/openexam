<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Enumerator.php
// Created: 2017-02-22 14:11:36
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Catalog\Attribute;

use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Principal;

/**
 * Attribute enumerator for directory service.
 * 
 * All methods has a short name equivalent too. For example, calling name(...)
 * yields the same result as getName(...).
 * 
 * @method array getAll(string $principal = null, boolean $single = true) Get all attributes (Principal::ATTR_ALL). The $principal argument defaults to caller.
 * @method array getAffiliation(string $principal = null, boolean $single = true) Get user affiliation (Principal::ATTR_AFFIL). The $principal argument defaults to caller.
 * @method array getAssurance(string $principal = null, boolean $single = true) Get data assurance (Principal::ATTR_ASSUR). The $principal argument defaults to caller.
 * @method array|string getGivenName(string $principal = null, boolean $single = true) Get firstname of user (Principal::ATTR_GN). The $principal argument defaults to caller.
 * @method array|string getFirstName(string $principal = null, boolean $single = true) Get firstname of user (Principal::ATTR_GN). The $principal argument defaults to caller.
 * @method array|string getMail(string $principal = null, boolean $single = true) Get email address(es) for user (Principal::ATTR_MAIL). The $principal argument defaults to caller.
 * @method array|string getEmail(string $principal = null, boolean $single = true) Get email address(es) for user (Principal::ATTR_MAIL). The $principal argument defaults to caller.
 * @method array|string getName(string $principal = null, boolean $single = true) Get name of user (Principal::ATTR_NAME). The $principal argument defaults to caller.
 * @method array|string getCommonName(string $principal = null, boolean $single = true) Get name of user (Principal::ATTR_NAME). The $principal argument defaults to caller.
 * @method array|string getPersonalNumber(string $principal = null, boolean $single = true) Get peronal number of user (Principal::ATTR_PNR). The $principal argument defaults to caller.
 * @method array|string getSocialNumber(string $principal = null, boolean $single = true) Get social number of user (Principal::ATTR_PNR). The $principal argument defaults to caller.
 * @method array|string getSirName(string $principal = null, boolean $single = true) Get lastname of user (Principal::ATTR_SN). The $principal argument defaults to caller.
 * @method array|string getLastName(string $principal = null, boolean $single = true) Get lastname of user (Principal::ATTR_SN). The $principal argument defaults to caller.
 * @method array|string getUserName(string $principal = null, boolean $single = true) Get username of user (Principal::ATTR_UID). The $principal argument defaults to caller.
 * @method array|string getUID(string $principal = null, boolean $single = true) Get username of user (Principal::ATTR_UID). The $principal argument defaults to caller.
 * @method array|string getDepartment(string $principal = null, boolean $single = true) Get department of user (non-standard attribute). The $principal argument defaults to caller.
 * 
 * These properties returns single attribute of caller (the logged in person).
 * 
 * @property-read array $all Get all attributes of caller (Principal::ATTR_ALL).
 * @property-read array $affiliation Get user affiliation for caller (Principal::ATTR_AFFIL).
 * @property-read array $assurance Get data assurance for caller (Principal::ATTR_ASSUR).
 * @property-read string $firstname Get firstname of caller (Principal::ATTR_GN).
 * @property-read string $lastname Get lastname of caller (Principal::ATTR_SN).
 * @property-read string $name Get name of caller (Principal::ATTR_NAME).
 * @property-read string $mail Get email address of caller (Principal::ATTR_MAIL).
 * @property-read string $persnr Get personal number of caller (Principal::ATTR_PNR).
 * @property-read string $socialnr Get social number of caller (Principal::ATTR_PNR).
 * @property-read string $uid Get username of caller (Principal::ATTR_UID).
 * @property-read string $user Get username of caller (Principal::ATTR_UID).
 * 
 * Notice:
 * ---------
 * At the moment we are not actually enumerating attributes. That could be done
 * by fetching the user principal object and store it in memory using the
 * principal name as key.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Enumerator
{

        /**
         * The directory service.
         * @var DirectoryService 
         */
        private $_service;
        /**
         * The attribute map.
         * @var array 
         */
        private static $map = array(
                'all'            => Principal::ATTR_ALL,
                'affiliation'    => Principal::ATTR_AFFIL,
                'assurance'      => Principal::ATTR_ASSUR,
                'gn'             => Principal::ATTR_GN,
                'givenname'      => Principal::ATTR_GN,
                'firstname'      => Principal::ATTR_GN,
                'sn'             => Principal::ATTR_SN,
                'sirname'        => Principal::ATTR_SN,
                'lastname'       => Principal::ATTR_SN,
                'name'           => Principal::ATTR_NAME,
                'commonname'     => Principal::ATTR_NAME,
                'mail'           => Principal::ATTR_MAIL,
                'email'          => Principal::ATTR_MAIL,
                'pnr'            => Principal::ATTR_PNR,
                'persnr'         => Principal::ATTR_PNR,
                'personalnumber' => Principal::ATTR_PNR,
                'social'         => Principal::ATTR_PNR,
                'socialnumber'   => Principal::ATTR_PNR,
                'uid'            => Principal::ATTR_UID,
                'user'           => Principal::ATTR_UID,
                'userid'         => Principal::ATTR_UID
        );

        /**
         * Constructor.
         * @param DirectoryService $service The directory service.
         */
        public function __construct($service)
        {
                $this->_service = $service;
        }

        public function __call($name, $arguments)
        {
                return $this->getAttribute($name, $arguments);
        }

        public function __get($name)
        {
                return $this->getAttribute($name, null);
        }

        public function getAttribute($name, $arguments)
        {
                // 
                // Support attribute calls:
                // 
                // 1. getName()                         -> single, caller
                // 2. getName($principal)               -> single, principal
                // 3. getName($principal, true)         -> single, principal
                // 4. getName($principal, false)        -> multi, principal
                // 5. getName(true)                     -> single, caller
                // 6. getName(false)                    -> multi, caller
                // 
                // This magic method also supports using short name equivalents
                // like name(true) or name($principal, false).
                // 
                // Decide on using single/multi and caller/principal:
                // 
                $single = true;
                $caller = true;

                if (isset($arguments[0])) {
                        if (is_bool($arguments[0])) {
                                $single = $arguments[0];
                        } elseif (is_string($arguments[0])) {
                                $caller = $arguments[0];
                        }
                }

                if (isset($arguments[1])) {
                        if (is_bool($arguments[1])) {
                                $single = $arguments[1];
                        }
                }

                // 
                // Method name is attribute:
                // 
                if (strncmp($name, 'get', 3) == 0) {
                        $attrib = strtolower(substr($name, 3));
                } else {
                        $attrib = strtolower($name);  // i.e. uid(...)
                }

                // 
                // Remap custom attributes:
                // 
                if (in_array($attrib, array_keys(self::$map))) {
                        $attrib = self::$map[$attrib];
                }

                // 
                // Return single or multi entries defined by arguments:
                // 
                if ($single) {
                        if (is_string($caller)) {
                                return $this->_service->getAttribute($attrib, $caller);
                        } else {
                                return $this->_service->getAttribute($attrib);
                        }
                } else {
                        if (is_string($caller)) {
                                return $this->_service->getAttributes($attrib, $caller);
                        } else {
                                return $this->_service->getAttributes($attrib);
                        }
                }
        }

}
