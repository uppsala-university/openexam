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

use Phalcon\Mvc\User\Component;

/**
 * Directory service manager.
 * 
 * This class maintains a register of directory services grouped by domains
 * and provides uniform queries against all services registered for a single
 * domain or all domains at once.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DirectoryManager extends Component implements DirectoryService
{

        /**
         * Default list of attributes returned.
         */
        static $DEFAULT_ATTR = array(Principal::ATTR_UID, Principal::ATTR_NAME, Principal::ATTR_MAIL);

        /**
         * Default search attribute.
         */
        const DEFAULT_SEARCH = Principal::ATTR_NAME;
        /**
         * Default limit on number of returned user principal objects.
         */
        const DEFAULT_LIMIT = 5;

        /**
         * The collection of directory services.
         * @var array 
         */
        private $_services;
        /**
         * Default search domain.
         * @var string 
         */
        private $_domain;

        /**
         * Constructor.
         * @param DirectoryService[] $services The collection of directory services.
         */
        public function __construct($services = array())
        {
                $this->_services = $services;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                foreach ($this->_services as $domain => $services) {
                        foreach ($services as $name => $service) {
                                if (($backend = $service->getConnection()) != null) {
                                        if ($backend->connected()) {
                                                try {
                                                        $backend->close();
                                                } catch (Exception $exception) {
                                                        $this->report($exception, $service, $name);
                                                }
                                        }
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
         * @param ServiceAdapter $service The directory service.
         * @param array|string $domains The domains.
         * @param string $name Directory service name (optional)
         */
        public function register($service, $domains, $name = null)
        {
                if (!is_array($domains)) {
                        $domains = array($domains);
                }
                if (isset($name)) {
                        $service->setName($name);
                }
                foreach ($domains as $domain) {
                        if (!isset($this->_services[$domain])) {
                                $this->_services[$domain] = array();
                        }
                        if (isset($name)) {
                                $this->_services[$domain][$name] = $service;
                        } else {
                                $this->_services[$domain][] = $service;
                        }
                }
        }

        /**
         * Get registered domains.
         * @return array
         */
        public function getDomains()
        {
                return array_keys($this->_services);
        }

        /**
         * Get services registered for domain.
         * @param string $domain The domain name.
         * @return DirectoryService[]
         */
        public function getServices($domain)
        {
                return $this->_services[$domain];
        }

        /**
         * Set default search domain.
         * @param string $domain The domain name.
         */
        public function setDefaultDomain($domain)
        {
                $this->_domain = $domain;
        }

        /**
         * Get array of default attributes for user principal searches.
         * @return array
         */
        public function getdefaultAttributes()
        {
                return self::$DEFAULT_ATTR;
        }

        /**
         * Get groups for user.
         * @param string $principal The user principal name.
         * @param array $attributes The attributes to return.
         * @return array
         */
        public function getGroups($principal, $attributes = array(Group::ATTR_NAME))
        {
                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->_services[$domain])) {
                        foreach ($this->_services[$domain] as $name => $service) {
                                try {
                                        if (($groups = $service->getGroups($principal, $attributes)) != null) {
                                                $result = array_merge($result, $groups);
                                        }
                                } catch (Exception $exception) {
                                        $this->report($exception, $service, $name);
                                }
                        }
                }

                return $result;
        }

        /**
         * Get members of group.
         * @param string $group The group name.
         * @param string $domain Restrict search to domain.
         * @param array $attributes The attributes to return.
         * @return Principal[]
         */
        public function getMembers($group, $domain = null, $attributes = array(Principal::ATTR_PN, Principal::ATTR_NAME, Principal::ATTR_MAIL))
        {
                $result = array();

                foreach ($this->_services as $dom => $services) {
                        if (!isset($domain) || $dom == $domain) {
                                foreach ($services as $name => $service) {
                                        try {
                                                if (($members = $service->getMembers($group, $dom, $attributes)) != null) {
                                                        $result = array_merge($result, $members);
                                                }
                                        } catch (Exception $exception) {
                                                $this->report($exception, $service, $name);
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get attribute (Principal::ATTR_XXX) for user.
         * 
         * <code>
         * // Get all email addresses:
         * $service->getAttribute('user@example.com', Principal::ATTR_MAIL);
         * 
         * // Get user given name:
         * $service->getAttribute('user@example.com', Principal::ATTR_GN);
         * </code>
         * 
         * @param string $principal The user principal name.
         * @param string $attribute The attribute to return.
         * @return array
         */
        public function getAttribute($principal, $attribute)
        {
                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->_services[$domain])) {
                        foreach ($this->_services[$domain] as $name => $service) {
                                try {
                                        if (($attributes = $service->getAttribute($principal, $attribute)) != null) {
                                                $result = array_merge($result, $attributes);
                                        }
                                } catch (Exception $exception) {
                                        $this->report($exception, $service, $name);
                                }
                        }
                }

                return $result;
        }

        /**
         * Get user principal object.
         * 
         * <code>
         * // Search three first Tomas in example.com domain:
         * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
         * 
         * // Get email for user tomas:
         * $manager->getPrincipal('thomas', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
         * 
         * // Get email for user principal name tomas@example.com:
         * $manager->getPrincipal('thomas@example.com', Principal::ATTR_PN, array('attr' => Principal::ATTR_MAIL));
         * </code>
         * 
         * The $options parameter is an array containing zero or more of 
         * these fields:
         * 
         * <code>
         * array(
         *       'attr'   => array(),   // attributes to return
         *       'limit'  => 0,         // limit number of entries
         *       'domain' => null,      // restrict to domain
         *       'data'   => true       // append search data in attr member
         * )
         * </code>
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query.
         * @param array $options Various search options.
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipal($needle, $search = self::DEFAULT_SEARCH, $options = array(
                'attr'   => null,
                'limit'  => self::DEFAULT_LIMIT,
                'domain' => null,
                'data'   => false
        ))
        {
                if (!isset($options['attr'])) {
                        $options['attr'] = self::$DEFAULT_ATTR;
                }
                if (!isset($options['limit'])) {
                        $options['limit'] = self::DEFAULT_LIMIT;
                }
                if (!isset($options['domain'])) {
                        $options['domain'] = $this->_domain;
                }
                if (!is_array($options['attr'])) {
                        $options['attr'] = array($options['attr']);
                }

                $result = array();

                foreach ($this->_services as $domain => $services) {
                        if (!isset($options['domain']) || $domain == $options['domain']) {
                                foreach ($services as $name => $service) {
                                        try {
                                                if (($res = $service->getPrincipal($needle, $search, $options)) != null) {
                                                        if ($options['limit'] == 0) {
                                                                $result = array_merge($result, $res);
                                                        } elseif (count($res) + count($result) < $options['limit']) {
                                                                $result = array_merge($result, $res);
                                                        } else {
                                                                $num = $options['limit'] - count($result);
                                                                $result = array_merge($result, array_slice($res, 0, $num));
                                                                return $result;
                                                        }
                                                }
                                        } catch (Exception $exception) {
                                                $this->report($exception, $service, $name);
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Specialization of getAttribute() for email addresses.
         * @param string $principal The user principal.
         * @return array
         */
        public function getMail($principal)
        {
                return $this->getAttribute($principal, Principal::ATTR_MAIL);
        }

        /**
         * Specialization of getAttribute() for common name.
         * @param string $principal The user principal.
         * @return array
         */
        public function getName($principal)
        {
                return $this->getAttribute($principal, Principal::ATTR_NAME);
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
                        return $this->_domain;   // Use default domain.
                }
        }

        /**
         * Report exception.
         * @param Exception $exception The exception to report.
         * @param DirectoryService $service The directory service.
         * @param string $name The directory service name (from config).
         */
        private function report($exception, $service, $name)
        {
                $this->logger->system->begin();
                $this->logger->system->error(
                    print_r(array(
                        'Exception' => get_class($exception),
                        'Message'   => $exception->getMessage(),
                        'Service'   => get_class($service) . ' [' . $name . ']',
                        'File'      => $exception->getFile(),
                        'Line'      => $exception->getLine(),
                        'Code'      => $exception->getCode()
                        ), true
                    )
                );
                $this->logger->system->commit();
        }

}
