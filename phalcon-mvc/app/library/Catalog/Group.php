<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
