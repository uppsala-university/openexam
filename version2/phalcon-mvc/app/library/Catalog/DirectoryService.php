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

/**
 * Interface for directory services.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface DirectoryService
{

        /**
         * Get groups for user.
         * @param string $principal The user principal name.
         * @param array $attributes The attributes to return.
         * @return array
         */
        function getGroups($principal, $attributes);

        /**
         * Get members of group.
         * @param string $group The group name.
         * @param string $domain Restrict search to domain.
         * @param array $attributes The attributes to return.
         * @return Principal[]
         */
        function getMembers($group, $domain, $attributes);

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
        function getAttribute($principal, $attribute);

        /**
         * Get user principal object.
         * 
         * <code>
         * // Search three first Tomas in example.com domain:
         * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
         * 
         * // Get email for tomas@example.com:
         * $manager->getPrincipal('thomas@example.com', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
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
         * @return Principal[] Matching user principal objects.
         */
        function getPrincipal($needle, $search, $options);
}
