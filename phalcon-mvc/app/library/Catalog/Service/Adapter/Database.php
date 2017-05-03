<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Database.php
// Created: 2016-11-14 03:13:10
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service\Adapter;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Catalog\Connection as ServiceConnection;
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
class Database extends AttributeService
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
                                Principal::ATTR_NAME  => 'displayName',
                                Principal::ATTR_GN    => 'givenName',
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
                            'conditions' => 'source = :source:',
                            'bind'       => array(
                                    'source' => $this->_source
                            ),
                            'group'      => 'domain',
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
         * Get multiple attribute (Principal::ATTR_XXX) for user.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name.
         * @return array
         */
        public function getAttributes($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }

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
         * Get single attribute (Principal::ATTR_XXX) for user.
         * 
         * Notice that affiliation and assurance are always returned as
         * array. For assurance its questionalble if it should return any
         * value at all.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (optional).
         * @return string|array
         */
        public function getAttribute($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }

                if (($user = User::findFirst(array(
                            'conditions' => 'principal = :principal:',
                            'bind'       => array(
                                    'principal' => $principal
                            )
                    )))) {
                        $data = $user->toArray();

                        if (isset($this->_attrmap['person'][$attribute])) {
                                $result = $data[$this->_attrmap['person'][$attribute]];
                        } else {
                                $result = $data[$attribute];
                        }

                        unset($user);
                        unset($data);

                        return $result;
                }
        }

        /**
         * Get user principal objects.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param array $options Various search options (optional).
         * 
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipals($needle, $search = null, $options = null)
        {
                $query = $this->getPrincipalQuery($needle, $search, $options);
                $array = $this->getPrincipalArray($query, $options, true);

                unset($query);
                return $array;
        }

        /**
         * Get user principal object.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param string $domain The search domain (optional).
         * @param array|string $attr The attributes to return (optional).
         * 
         * @return Principal The matching user principal object.
         */
        function getPrincipal($needle, $search = null, $domain = null, $attr = null)
        {
                $options = array(
                        'domain' => $domain,
                        'attr'   => $attr,
                        'limit'  => 1
                );

                $query = $this->getPrincipalQuery($needle, $search, $options);
                $array = $this->getPrincipalArray($query, $options, false);

                unset($query);

                if (is_array($array)) {
                        return $array[0];
                } else {
                        return null;
                }
        }

        /**
         * Get user principal objects.
         * 
         * @param Criteria $query The query criteria.
         * @param array $options Various search options.
         * @param boolean $addref Add service reference.
         * 
         * @return Principal[]
         */
        private function getPrincipalArray($query, $options, $addref)
        {
                $principals = array();

                if (($data = $query->execute())) {
                        foreach ($data->toArray() as $d) {
                                $principals[] = $this->getPrincipalObject($d, $options, $addref);
                        }
                }
                return $principals;
        }

        /**
         * Get principal object from data.
         * 
         * @param array $data The principal data.
         * @param array $options Various search options.
         * @param boolean $addref Add service reference.
         * 
         * @return Principal
         */
        private function getPrincipalObject($data, $options, $addref)
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
                }
                if ($addref) {
                        $principal->attr['svc'] = array(
                                'name' => $this->_name,
                                'type' => $this->_type,
                                'ref'  => $principal->attr['id']
                        );
                }
                if (isset($principal->attr['id'])) {
                        unset($principal->attr['id']);
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
                if (!empty($options['domain'])) {
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
                if (!empty($options['attr'])) {
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

                // 
                // Match attribute map agains user models column map:
                // 
                if (!empty($options['attr'])) {
                        $model = new User();
                        $attrmap = array_intersect($attrmap, $model->columnMap());
                }

                if (!isset($attrmap['id'])) {
                        $attrmap['id'] = 'id';
                }
                if (!empty($options['attr'])) {
                        $query->columns($attrmap);
                }
                if (!empty($options['limit'])) {
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
