<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Provider.php
// Created: 2016-11-13 14:05:18
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute;

/**
 * User attribute provider.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Provider
{

        /**
         * Return true if attributes are present.
         * @return boolean
         */
        function hasAttributes();

        /**
         * Get user attributes.
         * @return array 
         */
        function getAttributes();

        /**
         * Get user principal object.
         * @return Principal 
         */
        function getPrincipal();

        /**
         * Get user model.
         * @return User
         */
        function getUser();
}
