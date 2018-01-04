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
// File:    Group.php
// Created: 2014-11-05 23:09:23
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

/**
 * The group entity class.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Group
{

        /**
         * The group name attribute.
         */
        const ATTR_NAME = 'name';
        /**
         * The group description.
         */
        const ATTR_DESC = 'description';
        /**
         * The group member attribute.
         */
        const ATTR_MEMBER = 'member';
        /**
         * The parent group attribute.
         */
        const ATTR_PARENT = 'parent';

        /**
         * The group name.
         * @var string 
         */
        public $name;
        /**
         * The group description.
         * @var string 
         */
        public $description;
        /**
         * The group members.
         * @var array 
         */
        public $members;
        /**
         * The parent groups.
         * @var array 
         */
        public $parents;

}
