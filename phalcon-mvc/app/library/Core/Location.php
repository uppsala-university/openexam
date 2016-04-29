<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Location.php
// Created: 2015-04-09 01:52:38
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

use OpenExam\Models\Access;
use Phalcon\Mvc\User\Component;
use UUP\Authentication\Authenticator\DomainAuthenticator;
use UUP\Authentication\Restrictor\AddressRestrictor;

/**
 * Location information class.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Location extends Component
{

        /**
         * The locations information.
         * @var array 
         */
        private $_locations;

        /**
         * Constructor.
         * @param array $locations The location information.
         */
        public function __construct($locations = array())
        {
                $this->_locations = $locations;
        }

        /**
         * Get location entry for remote address.
         * 
         * This function returns the entry from the user configuration of
         * locations (the array passed to constructor). It can be used to 
         * display location aware information to the end user, for example
         * hardware usage information.
         * 
         * The default behavior is to return false for unmatched remote 
         * addresses. In some cases it might be more sensable to return some
         * fixed data for outside connections:
         * 
         * <code>
         * $entry = $location->getEntry(
         *      $remote, array(
         *              'addr' => $remote, 
         *              'desc' => 'Connection from outside',
         *              'pkey' => 'Extern'
         *      )
         * );
         * </code>
         * 
         * @param string $address The remote address.
         * @param mixed $default The default value if not found.
         * @return array
         */
        public function getEntry($address, $default = false)
        {
                foreach ($this->_locations as $okey => $oval) {
                        foreach ($oval as $ckey => $cval) {
                                foreach ($cval as $pkey => $pval) {
                                        if (is_numeric($pval['addr'][0])) {
                                                $validator = new AddressRestrictor($pval['addr']);
                                                if ($validator->match($address)) {
                                                        return array_merge($pval, array(
                                                                'okey' => $okey,
                                                                'ckey' => $ckey,
                                                                'pkey' => $pkey
                                                        ));
                                                }
                                        } elseif ($pval['addr'][0] == '|') {
                                                $validator = new DomainAuthenticator($pval['addr']);
                                                if ($validator->match(gethostbyaddr($address))) {
                                                        return array_merge($pval, array(
                                                                'okey' => $okey,
                                                                'ckey' => $ckey,
                                                                'pkey' => $pkey
                                                        ));
                                                }
                                        } else {
                                                if ($pval['addr'] == $address) {
                                                        return array_merge($pval, array(
                                                                'okey' => $okey,
                                                                'ckey' => $ckey,
                                                                'pkey' => $pkey
                                                        ));
                                                }
                                        }
                                }
                        }
                }

                return $default;
        }

        /**
         * Get entries list.
         * 
         * This function support query the system for locations. The list can
         * be done for allowing user to select the locations from where an
         * exam should be accessable.
         * 
         * The $filter option defines the sources from where location entries
         * should be returned. Its possible to return entries active exam,
         * recent used locations and the system pre-defined locations. 
         * 
         * The $filter option is a boolean map with the following keys:
         * 
         * <ul>
         * <li>system: Include entries from pre-defined locations in the system/user configuration.</li>
         * <li>recent: Include entries from user (the caller) recent used locations.</li>
         * <li>active: Include entries from current exam (the $exam option).
         * </ul>
         * 
         * Active entries is implicit discarded in the returned array if
         * $exam == 0. If requested entries are missing (e.g. active or recent),
         * the false is returned as their value.
         * 
         * If $flat is true, then the result array is collapsed from hierarchial
         * structure to more compact format where the "tree" of organization (O), 
         * campus (C) and place (P) are converted to "O -> C -> P" array keys
         * containing respective entry data.
         * 
         * @param int $exam The exam ID.
         * @param array $filter Location source filtering options.
         * @param boolean $flat Collapse result array.
         * @return array
         */
        public function getEntries($exam = 0, $filter = array(
                'system' => true,
                'recent' => true,
                'active' => true
        ), $flat = false)
        {
                $result = array();

                if ($exam == 0) {
                        $filter['active'] = false;
                }

                // 
                // Build tree array structure:
                // 
                if (isset($filter['system']) && $filter['system']) {
                        $result['system'] = $this->_locations;
                }
                if (isset($filter['recent']) && $filter['recent']) {
                        $result['recent'] = $this->user->settings->get(Settings::KEY_ACCESS);
                }
                if (isset($filter['active']) && $filter['active']) {
                        if (($access = Access::find(array(
                                    'exam_id = :exam:',
                                    'bind' => array(
                                            'exam' => $exam
                                    )
                            ))) != false) {
                                $result['active'] = array();
                                foreach ($access as $acc) {
                                        $path = explode(';', $acc->name);
                                        $result['active'][$path[0]][$path[1]][$path[2]] = $acc->toArray();
                                }
                        } else {
                                $result['active'] = false;
                        }
                }

                // 
                // Flatten result if requested:
                // 
                if ($flat) {
                        foreach ($result as $skey => $sval) {
                                if ($sval) {
                                        foreach ($sval as $okey => $oval) {
                                                foreach ($oval as $ckey => $cval) {
                                                        foreach ($cval as $pkey => $pval) {
                                                                $nkey = sprintf("%s -> %s -> %s", $okey, $ckey, $pkey);
                                                                $result[$skey][$nkey] = $pval;
                                                                unset($result[$skey][$okey][$ckey][$pkey]);
                                                        }
                                                        unset($result[$skey][$okey][$ckey]);
                                                }
                                                unset($result[$skey][$okey]);
                                        }
                                }
                        }
                }

                return $result;
        }

        /**
         * Get active location entries.
         * @param int $exam The exam ID.
         * @return array
         */
        public function getActive($exam)
        {
                return $this->getEntries($exam, array('active' => true), true)['active'];
        }

        /**
         * Get recent location entries.
         * @return array
         */
        public function getRecent()
        {
                return $this->getEntries(0, array('recent' => true), true)['recent'];
        }

        /**
         * Get system (user) config location entries.
         * @return array
         */
        public function getSystem()
        {
                return $this->getEntries(0, array('system' => true), true)['system'];
        }

}
