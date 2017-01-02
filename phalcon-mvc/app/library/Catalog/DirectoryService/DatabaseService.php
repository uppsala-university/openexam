<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DatabaseService.php
// Created: 2016-11-14 03:13:10
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Catalog\ServiceConnection;
use OpenExam\Models\User;
use Phalcon\Mvc\Model\Criteria;

/**
 * Catalog service based on user model.
 * 
 * Each user attribute entry in the user model has an source value that is
 * mapped against the authenticator (the attribute provider). In most cases
 * its of zero interest to know though.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DatabaseService extends AttributeService
{

        /**
         * List of domains.
         * @var array 
         */
        private $_domains;
        /**
         * The source value.
         * @var string 
         */
        private $_source;

        /**
         * Construct
         * @param string $source The source value.
         */
        public function __construct($source)
        {
                $this->_source = $source;
                parent::__construct(array(
                        'person' => array(
                                Principal::ATTR_UID   => 'uid',
                                Principal::ATTR_SN    => 'sn',
                                Principal::ATTR_NAME  => 'display_name',
                                Principal::ATTR_GN    => 'given_name',
                                Principal::ATTR_MAIL  => 'mail',
                                Principal::ATTR_PNR   => 'pnr',
                                Principal::ATTR_PN    => 'principal',
                                Principal::ATTR_AFFIL => 'affiliation',
                                Principal::ATTR_ALL   => '*'
                        )
                ));

                $this->_type = 'data';
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_domains);
                unset($this->_source);
                parent::__destruct();
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return null;
        }

        /**
         * Get dynamic list of supported domains.
         * @return array
         */
        public function getDomains()
        {
                if (isset($this->_domains)) {
                        return $this->_domains;
                }

                if (($domains = User::find(array(
                            'conditions' => array(
                                    'source = :source:',
                                    'bind' => array(
                                            'source' => $this->_source
                                    )
                            ),
                            'distinct'   => 'domain',
                            'columns'    => 'domain'
                    ))) != null) {
                        $this->_domains = array();
                        foreach ($domains as $domain) {
                                $this->_domains[] = $domain['domain'];
                                unset($domain);
                        }

                        unset($domains);
                        return $this->_domains;
                }
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
                if (($user = User::findFirst(array(
                            'conditions' => 'principal = :principal:',
                            'bind'       => array(
                                    'principal' => $principal
                            )
                    )))) {
                        $data = $user->toArray();

                        $result = array(
                                'svc' => array(
                                        'name' => $this->_name,
                                        'type' => $this->_type,
                                        'ref'  => $user->id
                        ));

                        if (isset($this->_attrmap['person'][$attribute])) {
                                $result[$attribute] = $data[$this->_attrmap['person'][$attribute]];
                        } else {
                                $result[$attribute] = $data[$attribute];
                        }

                        if (!is_array($result[$attribute])) {
                                $result[$attribute] = array($result[$attribute]);
                        }

                        unset($user);
                        unset($data);

                        return array($result);
                }
        }

        /**
         * Get user principal objects.
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
         *       'attr'   => array(),
         *       'limit'  => 0,
         *       'domain' => null
         * )
         * </code>
         * 
         * The attr field defines which attributes to return. The limit field 
         * limits the number of returned user principal objects (use 0 for 
         * unlimited). The query can be restricted to a single domain by 
         * setting the domain field.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query.
         * @param array $options Various search options.
         * 
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipal($needle, $search, $options)
        {
                $query = $this->getPrincipalQuery($needle, $search, $options);
                $array = $this->getPrincipalArray($query, $options);

                unset($query);
                return $array;
        }

        /**
         * Get user principal objects.
         * 
         * @param Criteria $query The query criteria.
         * @param array $options Various search options.
         * 
         * @return Principal[]
         */
        private function getPrincipalArray($query, $options)
        {
                $principals = array();

                if (($data = $query->execute())) {
                        foreach ($data->toArray() as $d) {
                                $principals[] = $this->getPrincipalObject($d, $options);
                        }
                }
                return $principals;
        }

        /**
         * Get principal object from data.
         * 
         * @param array $data The principal data.
         * @param array $options Various search options.
         * 
         * @return Principal
         */
        private function getPrincipalObject($data, $options)
        {
                $principal = new Principal();

                // 
                // Populate public properties in principal object:
                // 
                foreach ($data as $attr => $attrs) {
                        if (property_exists($principal, $attr)) {
                                if ($attr == Principal::ATTR_MAIL) {
                                        $principal->mail = $attrs;
                                        unset($data[$attr]);
                                } elseif ($attr == Principal::ATTR_AFFIL) {
                                        $affilation = $this->_affiliation;
                                        $principal->affiliation = unserialize($affilation($attrs));
                                        unset($data[$attr]);
                                } else {
                                        $principal->$attr = $attrs;
                                        unset($data[$attr]);
                                }
                        }
                }

                // 
                // Any left over attributes goes in attr member:
                // 
                if ($options) {
                        $principal->attr = $data;
                } else {
                        $principal->attr['svc'] = $data['svc'];
                }

                if (isset($principal->attr[Principal::ATTR_ASSUR])) {
                        $principal->attr[Principal::ATTR_ASSUR] = unserialize($principal->attr[Principal::ATTR_ASSUR]);
                }

                return $principal;
        }

        /**
         * Get query criteria.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query.
         * @param array $options Various search options.
         * 
         * @return Criteria
         */
        private function getPrincipalQuery($needle, $search, $options)
        {
                // 
                // Dynamic build search criteria. The search values might come from
                // client side, so use bind parameter.
                // 
                $query = User::query();

                // 
                // Remap from generic attribute name to service specific. The source is
                // i.e. the name of the federation from where attributes were provided.
                // 
                $query->where(sprintf(
                        "%s = :%s: AND source = :source:", $this->_attrmap['person'][$search], $search
                    ), array(
                        $search  => $needle,
                        'source' => $this->_source
                    )
                );

                // 
                // Add another filter on domain if requested. The database attribute 
                // service are typical multi-domain.
                // 
                if (isset($options['domain'])) {
                        $query->andWhere(sprintf(
                                "domain = :domain:"
                            ), array(
                                'domain' => $options['domain']
                            )
                        );
                }

                // 
                // Adjustment for simplified attribute queries:
                // 
                if ($options['attr'] == Principal::ATTR_ALL) {
                        unset($options['attr']);
                }
                if (is_string($options['attr'])) {
                        $options['attr'] = array($options['attr']);
                }

                // 
                // Map formal principal attribute names to model specific:
                // 
                if (isset($options['attr'])) {
                        $attrmap = $this->_attrmap['person'];

                        $insert = array_diff($options['attr'], array_keys($attrmap));
                        $remove = array_diff(array_keys($attrmap), $options['attr']);

                        foreach ($remove as $attribute) {
                                unset($attrmap[$attribute]);
                        }
                        foreach ($insert as $attribute) {
                                $attrmap[$attribute] = $attribute;
                        }
                }

                if (isset($options['attr'])) {
                        $query->columns($attrmap);
                }
                if (isset($options['limit'])) {
                        $query->limit($options['limit']);
                }

                // 
                // Cleanup:
                // 
                unset($attrmap);
                unset($insert);
                unset($remove);

                // 
                // Finally return result:
                // 
                return $query;
        }

}
