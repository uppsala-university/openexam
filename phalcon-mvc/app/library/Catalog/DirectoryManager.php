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
 * The magic attribute methods. All these methods has a short name equivalent
 * too, i.e. getName(...) vs. name(...). The user principal defaults to caller
 * if unset.
 * 
 * @method array getAffiliation(string $principal = null, boolean $single = true) Get user affiliation (Principal::ATTR_AFFIL).
 * @method array getAssurance(string $principal = null, boolean $single = true) Get data assurance (Principal::ATTR_ASSUR).
 * @method array|string getName(string $principal = null, boolean $single = true) Get name of user (Principal::ATTR_NAME).
 * @method array|string getFirstName(string $principal = null, boolean $single = true) Get firstname of user (Principal::ATTR_GN).
 * @method array|string getLastName(string $principal = null, boolean $single = true) Get lastname of user (Principal::ATTR_SN).
 * @method array|string getMail(string $principal = null, boolean $single = true) Get email address(es) for user (Principal::ATTR_MAIL).
 * @method array|string getPersonalNumber(string $principal = null, boolean $single = true) Get peronal number of user (Principal::ATTR_PNR).
 * @method array|string getSocialNumber(string $principal = null, boolean $single = true) Get social number of user (Principal::ATTR_PNR).
 * @method array|string getUID(string $principal = null, boolean $single = true) Get username of user (Principal::ATTR_UID).
 *
 * @property-read Attribute\Enumerator $attrib Get attribute enumerator.
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
         * The directory cache.
         * @var DirectoryCache 
         */
        private $_cache;
        /**
         * The attribute enumerator.
         * @var Attribute\Enumerator 
         */
        private $_enumerator;

        /**
         * Constructor.
         * @param DirectoryService[] $services The collection of directory services.
         */
        public function __construct($services = array())
        {
                $this->_services = $services;
                $this->_enumerator = new Attribute\Enumerator($this);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                foreach ($this->_services as $domain => $services) {
                        foreach ($services as $name => $service) {
                                if (($backend = $service->getConnection()) != null) {
                                        try {
                                                if ($backend->connected()) {
                                                        $backend->close();
                                                }
                                        } catch (Exception $exception) {
                                                $this->report($exception, $service, $name);
                                        } finally {
                                                unset($backend);
                                        }
                                }
                        }
                }

                unset($this->_domain);
                unset($this->_services);
                unset($this->_enumerator);
        }

        public function __call($name, $arguments)
        {
                return $this->_enumerator->getAttribute($name, $arguments);
        }

        public function __get($name)
        {
                if ($propertyName == 'attrib') {
                        return $this->_enumerator;
                } else {
                        return parent::__get($name);
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
                if (!isset($domains) || $domains == '*') {
                        $domains = $service->getDomains();
                }
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
         * Set directory cache.
         * @param DirectoryCache $cache The directory cache.
         */
        public function setCache($cache)
        {
                $this->_cache = $cache;
        }

        /**
         * Get directory cache.
         * @return DirectoryCache
         */
        public function getCache()
        {
                return $this->_cache;
        }

        /**
         * Get attributes enumerator.
         * @return Attribute\Enumerator
         */
        public function getEnumerator()
        {
                return $this->_enumerator;
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
         * Get service.
         * @param string $name The service name.
         * @return DirectoryService 
         */
        public function getService($name)
        {
                foreach ($this->_services as $services) {
                        foreach ($services as $sname => $service) {
                                if ($sname == $name) {
                                        return $service;
                                }
                        }
                }
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
        public function getDefaultAttributes()
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
                if (($result = $this->_cache->getGroups($principal, $attributes))) {
                        return $result;
                }

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

                $this->_cache->setGroups($principal, $attributes, $result);
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
                if (($result = $this->_cache->getMembers($group, $domain, $attributes))) {
                        return $result;
                }

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

                $this->_cache->setMembers($group, $domain, $attributes, $result);
                return $result;
        }

        /**
         * Get single attribute (Principal::ATTR_XXX) for user.
         * 
         * Returns first found attribute from directory service. Use 
         * getAttributes() to get full set of attributes from all directory 
         * serices.
         * 
         * If $principal argument is missing, then it defaults to calling
         * user that has to be authenticated.
         * 
         * <code>
         * // Get email address of caller:
         * $service->getAttribute(Principal::ATTR_MAIL);
         * 
         * // Get email address for user@example.com:
         * $service->getAttribute(Principal::ATTR_MAIL, 'user@example.com');
         * 
         * // Get given name for user@example.com:
         * $service->getAttribute('user@example.com', Principal::ATTR_GN);
         * </code>
         * 
         * Notice that affiliation and assurance are always returned as
         * array. For assurance its questionalble if it should return any
         * value at all.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (defaults to caller).
         * @return string|array
         * 
         * @see getAttributes()
         */
        public function getAttribute($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }
                if (($result = $this->_cache->getAttribute($attribute, $principal))) {
                        return $result;
                }

                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->_services[$domain])) {
                        foreach ($this->_services[$domain] as $name => $service) {
                                try {
                                        if (($attributes = $service->getAttribute($attribute, $principal)) != null) {
                                                if (is_array($attributes)) {
                                                        $result = array_merge($result, $attributes);
                                                } elseif (is_null($attributes)) {
                                                        continue;       // try next
                                                } else {
                                                        $result = $attributes;
                                                        break;
                                                }
                                        }
                                } catch (Exception $exception) {
                                        $this->report($exception, $service, $name);
                                }
                        }
                }

                if (count($result) == 0) {
                        $result = null;
                }
                if (is_array($result)) {
                        $result = array_unique($result);
                }

                // 
                // Fix for attributes having multiple values:
                // 
                if ($attribute == Principal::ATTR_AFFIL ||
                    $attribute == Principal::ATTR_ASSUR) {
                        if (is_null($result)) {
                                $result = array();
                        }
                }

                $this->_cache->setAttribute($attribute, $principal, serialize($result));
                return $result;
        }

        /**
         * Get multiple attributes (Principal::ATTR_XXX) for user.
         * 
         * Returns all attribute from all directory services. A single user
         * might occure in multiple directory services. Each service might also
         * return multiple attributes (mail addresses is a typical case).
         * 
         * If $principal argument is missing, then it defaults to calling
         * user that has to be authenticated.
         * 
         * <code>
         * // Get email addresses of caller:
         * $service->getAttributes(Principal::ATTR_MAIL);
         * 
         * // Get email addresses for user@example.com:
         * $service->getAttributes(Principal::ATTR_MAIL, 'user@example.com');
         * 
         * // Get given names for user@example.com:
         * $service->getAttributes(Principal::ATTR_GN, 'user@example.com');
         * </code>
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (defaults to caller).
         * @return array
         * 
         * @see getAttribute()
         */
        public function getAttributes($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }
                if (($result = $this->_cache->getAttributes($attribute, $principal))) {
                        return $result;
                }

                $domain = $this->getDomain($principal);
                $result = array();

                if (isset($this->_services[$domain])) {
                        foreach ($this->_services[$domain] as $name => $service) {
                                try {
                                        if (($attributes = $service->getAttributes($attribute, $principal)) != null) {
                                                $result = array_merge($result, $attributes);
                                        }
                                } catch (Exception $exception) {
                                        $this->report($exception, $service, $name);
                                }
                        }
                }

                $this->_cache->setAttributes($attribute, $principal, $result);
                return $result;
        }

        /**
         * Get multiple user principal objects.
         * 
         * The $needle defines the search string and $search defines the search
         * type. The options parameter defines common search options (i.e. limit
         * on returned records) or which attributes to return.
         * 
         * Supported options are:
         * <code>
         * $options = array(
         *       'attr'   => array(),   // An string or array
         *       'limit'  => 0,         // Use 0 for unlimited
         *       'domain' => null       // The domain filter
         * )
         * </code>
         * 
         * The attr in $options defines which properties to set in returned
         * user principal objects. Non-standard attributes are populated in
         * the attr member of the user principal class.
         * 
         * Some examples:
         * <code>
         * // Search for users named Thomas in all domains:
         * $manager->getPrincipal('Thomas');
         * 
         * // Search three first Tomas in example.com domain:
         * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
         * 
         * // Get email attributes for user thomas:
         * $manager->getPrincipal('thomas', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
         * 
         * // Get email attributes for user principal name thomas@example.com:
         * $manager->getPrincipal('thomas@example.com', Principal::ATTR_PN, array('attr' => Principal::ATTR_MAIL));
         * </code>
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param array $options Various search options (optional).
         * 
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipals($needle, $search = null, $options = null)
        {
                if (!isset($search) || $search == false) {
                        $search = self::DEFAULT_SEARCH;
                }
                if (!isset($options) || $options == false) {
                        $options = array();
                }
                if (!isset($options['attr']) || $options['attr'] == false) {
                        $options['attr'] = self::$DEFAULT_ATTR;
                }
                if (!isset($options['limit']) || $options['limit'] == false) {
                        $options['limit'] = self::DEFAULT_LIMIT;
                }
                if (!isset($options['domain']) || $options['domain'] == false) {
                        $options['domain'] = $this->_domain;
                }
                if (!is_array($options['attr'])) {
                        $options['attr'] = array($options['attr']);
                }

                if (($result = $this->_cache->getPrincipals($needle, $search, $options))) {
                        return $result;
                }

                $result = array();

                foreach ($this->_services as $domain => $services) {
                        if (!isset($options['domain']) || $domain == $options['domain']) {
                                foreach ($services as $name => $service) {
                                        try {
                                                if (($res = $service->getPrincipals($needle, $search, $options)) != null) {
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

                $this->_cache->setPrincipals($needle, $search, $options, $result);
                return $result;
        }

        /**
         * Get single user principal object.
         * 
         * Similar to getPrincipals(), but only returns null (not found) or
         * a single principal object. Use $domain to restrict search scope.
         * The $attr array is the attributes to fetch and populate in the
         * principal object returned.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param string $domain The search domain (optional).
         * @param array|string $attr The attributes to return (optional).
         * 
         * @return Principal The matching user principal object.
         */
        public function getPrincipal($needle, $search = null, $domain = null, $attr = null)
        {
                if (!isset($search) || $search == false) {
                        $search = self::DEFAULT_SEARCH;
                }
                if (!isset($attr) || $attr == false) {
                        $attr = self::$DEFAULT_ATTR;
                }
                if (!isset($domain) || $domain == false) {
                        $domain = $this->_domain;
                }
                if (!is_array($attr)) {
                        $attr = array($attr);
                }

                if (($result = $this->_cache->getPrincipal($needle, $search, $domain, $attr))) {
                        return $result;
                }

                foreach ($this->_services as $dom => $services) {
                        if (!isset($domain) || $domain == $dom) {
                                foreach ($services as $name => $service) {
                                        try {
                                                if (($result = $service->getPrincipal($needle, $search, $domain, $attr)) != null) {
                                                        break;
                                                }
                                        } catch (Exception $exception) {
                                                $this->report($exception, $service, $name);
                                        }
                                }
                        }
                }

                $this->_cache->setPrincipal($needle, $search, $domain, $attr, $result);
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

        /**
         * Get service connection.
         * 
         * This method will always return null as the service connection is not
         * unique withing the manager, its a container for multiple directory 
         * services delivering catalog data for one or more domains.
         * 
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return null;
        }

        /**
         * Get service name.
         * @return string
         */
        public function getServiceName()
        {
                return 'manager';
        }

}
