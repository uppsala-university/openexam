<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceQuery.php
// Created: 2017-04-24 18:14:42
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Catalog;

/**
 * Interface for directory queries.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface DirectoryQuery
{

        /**
         * Get groups for user.
         * 
         * @param string $principal The user principal name.
         * @param array $attributes The attributes to return.
         * @return array
         */
        function getGroups($principal, $attributes = null);

        /**
         * Get members of group.
         * 
         * @param string $group The group name.
         * @param string $domain Restrict search to domain (optional).
         * @param array $attributes The attributes to return (optional).
         * @return Principal[]
         */
        function getMembers($group, $domain = null, $attributes = null);

        /**
         * Get single attribute (Principal::ATTR_XXX) for user.
         * 
         * Returns first found attribute from directory service. Use 
         * getAttributes() to get full set of attributes from all directory 
         * serices.
         * 
         * If $principal argument is missing, then it defaults to calling
         * user that has to be authenticated.
         * 
         * <code>
         * // Get email address of caller:
         * $service->getAttribute(Principal::ATTR_MAIL);
         * 
         * // Get email address for user@example.com:
         * $service->getAttribute(Principal::ATTR_MAIL, 'user@example.com');
         * 
         * // Get given name for user@example.com:
         * $service->getAttribute('user@example.com', Principal::ATTR_GN);
         * </code>
         * 
         * Notice that affiliation and assurance are always returned as
         * array. For assurance its questionalble if it should return any
         * value at all.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (defaults to caller).
         * @return string|array
         * 
         * @see getAttributes()
         */
        function getAttribute($attribute, $principal = null);

        /**
         * Get multiple attributes (Principal::ATTR_XXX) for user.
         * 
         * Returns all attribute from all directory services. A single user
         * might occure in multiple directory services. Each service might also
         * return multiple attributes (mail addresses is a typical case).
         * 
         * If $principal argument is missing, then it defaults to calling
         * user that has to be authenticated.
         * 
         * <code>
         * // Get email addresses of caller:
         * $service->getAttributes(Principal::ATTR_MAIL);
         * 
         * // Get email addresses for user@example.com:
         * $service->getAttributes(Principal::ATTR_MAIL, 'user@example.com');
         * 
         * // Get given names for user@example.com:
         * $service->getAttributes(Principal::ATTR_GN, 'user@example.com');
         * </code>
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name (defaults to caller).
         * @return array
         * 
         * @see getAttribute()
         */
        function getAttributes($attribute, $principal = null);

        /**
         * Get multiple user principal objects.
         * 
         * The $needle defines the search string and $search defines the search
         * type. The options parameter defines common search options (i.e. limit
         * on returned records) or which attributes to return.
         * 
         * Supported options are:
         * <code>
         * $options = array(
         *       'attr'   => array(),   // An string or array
         *       'limit'  => 0,         // Use 0 for unlimited
         *       'domain' => null       // The domain filter
         * )
         * </code>
         * 
         * The attr in $options defines which properties to set in returned
         * user principal objects. Non-standard attributes are populated in
         * the attr member of the user principal class.
         * 
         * Some examples:
         * <code>
         * // Search for users named Thomas in all domains:
         * $manager->getPrincipal('Thomas');
         * 
         * // Search three first Tomas in example.com domain:
         * $manager->getPrincipal('Thomas', Principal::ATTR_GN, array('domain' => 'example.com', 'limit' => 3));
         * 
         * // Get email attributes for user thomas:
         * $manager->getPrincipal('thomas', Principal::ATTR_UID, array('attr' => Principal::ATTR_MAIL));
         * 
         * // Get email attributes for user principal name thomas@example.com:
         * $manager->getPrincipal('thomas@example.com', Principal::ATTR_PN, array('attr' => Principal::ATTR_MAIL));
         * </code>
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param array $options Various search options (optional).
         * 
         * @return Principal[] Matching user principal objects.
         */
        function getPrincipals($needle, $search = null, $options = null);

        /**
         * Get single user principal object.
         * 
         * Similar to getPrincipals(), but only returns null (not found) or
         * a single principal object. Use $domain to restrict search scope.
         * The $attr array is the attributes to fetch and populate in the
         * principal object returned.
         * 
         * @param string $needle The attribute search string.
         * @param string $search The attribute to query (optional).
         * @param string $domain The search domain (optional).
         * @param array|string $attr The attributes to return (optional).
         * 
         * @return Principal The matching user principal object.
         */
        function getPrincipal($needle, $search = null, $domain = null, $attr = null);
}
