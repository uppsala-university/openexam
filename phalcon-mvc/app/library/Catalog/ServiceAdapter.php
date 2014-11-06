<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceAdapter.php
// Created: 2014-10-22 04:17:43
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use Phalcon\Mvc\User\Component;

/**
 * Directory service adapter.
 * 
 * Classes implementing the DirectoryService interface should derive from 
 * this class and override any methods from this class to provide required 
 * functionality. This class provides dummy fallback methods.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ServiceAdapter extends Component implements DirectoryService
{

        public function getGroups($principal, $attributes)
        {
                return null;
        }

        public function getMembers($group, $domain, $attributes)
        {
                return null;
        }

        public function getAttribute($principal, $attr)
        {
                return null;
        }

        public function getPrincipal($needle, $search, $options)
        {
                return null;
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return null;
        }

}
