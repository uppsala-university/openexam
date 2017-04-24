<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DirectoryService.php
// Created: 2014-10-22 03:35:53
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use OpenExam\Library\Catalog\Service\Connection;

/**
 * Interface for directory services.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface DirectoryService extends DirectoryQuery
{

        /**
         * Identifier of prefered value in attribute collection.
         */
        const PRIMARY_ATTR_VALUE = 'x-primary';
        /**
         * Identifier of english translation attribute value.
         */
        const PRIMARY_LANG_ENGLISH = 'lang-en';
        /**
         * Identifier of swedish translation attribute value.
         */
        const PRIMARY_LANG_SWEDISH = 'lang-en';

        /**
         * Get service connection.
         * @return Connection 
         */
        function getConnection();

        /**
         * Get service name.
         * @return string
         */
        function getServiceName();

        /**
         * Get service domains.
         * @return array
         */
        function getDomains();

        /**
         * Set user domain.
         * @param string $domain The search domain.
         */
        function setDomain($domain);

        /**
         * Set attributes filter.
         * @param array $attributes The returned attributes.
         */
        function setFilter($attributes);

        /**
         * Get user domain.
         */
        function getDomain();

        /**
         * Get attributes filter.
         * @return array
         */
        function getFilter();
}
