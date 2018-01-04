<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
