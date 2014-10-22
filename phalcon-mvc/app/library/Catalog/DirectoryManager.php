<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DirectoryManager.php
// Created: 2014-10-22 03:44:35
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

/**
 * Directory service manager.
 * 
 * This class maintains a register of directory services 
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DirectoryManager implements DirectoryService
{

        /**
         * The collection of directory services.
         * @var array 
         */
        private $services;

        /**
         * Constructor.
         * @param array $services The collection of directory services.
         */
        public function __construct($services = array())
        {
                $this->services = $services;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                foreach ($this->services as $domain => $services) {
                        foreach ($services as $service) {
                                if ($service->connected()) {
                                        $service->close();
                                }
                        }
                }
        }

        /**
         * Register an directory service.
         * 
         * This function register an direcory service as a catalog for
         * one or more domains.
         *  
         * @param array $domains The domains.
         * @param DirectoryService $service The directory service.
         */
        public function register($service, $domains)
        {
                foreach ($domains as $domain) {
                        if (!isset($this->services[$domain])) {
                                $this->services[$domain] = array();
                        }
                        $this->services[$domain][] = $service;
                }
        }

        /**
         * Get email addresses for user.
         * @param string $principal The user principal name.
         * @return array
         */
        public function getEmail($principal)
        {
                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->services[$domain])) {
                        foreach ($this->services[$domain] as $services) {
                                foreach ($services as $service) {
                                        if (!$service->connected()) {
                                                $service->open();
                                        }
                                        if (($emails = $service->getEmail($principal)) != null) {
                                                $result = array_merge($result, $emails);
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get groups for user.
         * @param string $principal The user principal name.
         * @return array
         */
        public function getGroups($principal)
        {
                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->services[$domain])) {
                        foreach ($this->services[$domain] as $services) {
                                foreach ($services as $service) {
                                        if (!$service->connected()) {
                                                $service->open();
                                        }
                                        if (($groups = $service->getGroups($principal)) != null) {
                                                $result = array_merge($result, $groups);
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get members of group.
         * @param string $group The group name.
         * @return array
         */
        public function getMembers($group, $domain = null)
        {
                $result = array();

                foreach ($this->services as $dom => $services) {
                        if (!isset($domain) || $dom == $domain) {
                                foreach ($services as $service) {
                                        if (!$service->connected()) {
                                                $service->open();
                                        }
                                        if (($members = $service->getMembers($group)) != null) {
                                                $result = array_merge($result, $members);
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get domain part from principal name.
         * @param string $principal The user principal name.
         * @return string
         */
        private function getDomain($principal)
        {
                if (($pos = strpos($principal, '@'))) {
                        return substr($principal, ++$pos);
                } else {
                        return "";
                }
        }

}
