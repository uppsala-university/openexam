<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Swamid.php
// Created: 2016-11-13 21:21:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute\Profile;

use OpenExam\Library\Catalog\Attribute\Profile;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Models\User;

/**
 * The SWAMID attribute profile.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Swamid implements Profile
{

        /**
         * The principal name attribute.
         */
        const ATTR_PN = 'eduPersonPrincipalName';
        /**
         * The user affiliation attribute.
         */
        const ATTR_AFFIL = 'eduPersonScopedAffiliation';
        /**
         * The UID attribute.
         */
        const ATTR_UID = 'uid';
        /**
         * The name attribute.
         */
        const ATTR_NAME = 'cn';
        /**
         * The sirname attribute.
         */
        const ATTR_SN = 'sn';
        /**
         * The given name attribute.
         */
        const ATTR_GN = 'gn';
        /**
         * The mail attribute.
         */
        const ATTR_MAIL = 'mail';
        /**
         * The personal number attribute.
         */
        const ATTR_PNR = 'norEduPersonNIN';
        /**
         * The display name attribute.
         */
        const ATTR_DISP = 'displayName';
        /**
         * Organization acronym (i.e. UU) attribute.
         */
        const ATTR_ACRONYM = 'norEduOrgAcronym';
        /**
         * The user attributes assurance attribute.
         */
        const ATTR_ASSURANCE = 'eduPersonAssurance';
        /**
         * The home organization attribute.
         */
        const ATTR_HOME = 'schacHomeOrganization';

        /**
         * User attribute map.
         * @var array 
         */
        private static $attrmap = array(
                self::ATTR_AFFIL     => 'affiliation',
                self::ATTR_PN        => 'principal',
                self::ATTR_PNR       => 'pnr',
                self::ATTR_ACRONYM   => 'acronym',
                self::ATTR_ASSURANCE => 'assurance',
                self::ATTR_HOME      => 'home'
        );

        /**
         * Get user principal object.
         * @param array $attr The attributes array.
         * @return Principal 
         */
        public function getPrincipal($attr)
        {
                $principal = new Principal();

                $principal->attr = $attr;
                if (isset($attr[self::ATTR_AFFIL])) {
                        $principal->affiliation = $attr[self::ATTR_AFFIL];
                }
                if (isset($attr[self::ATTR_GN])) {
                        $principal->gn = $attr[self::ATTR_GN][0];
                }
                if (isset($attr[self::ATTR_MAIL])) {
                        $principal->mail = $attr[self::ATTR_MAIL][0];
                }
                if (isset($attr[self::ATTR_DISP])) {
                        $principal->name = $attr[self::ATTR_DISP][0];
                }
                if (isset($attr[self::ATTR_PNR])) {
                        $principal->pnr = $attr[self::ATTR_PNR][0];
                }
                if (isset($attr[self::ATTR_PN])) {
                        $principal->principal = $attr[self::ATTR_PN][0];
                }
                if (isset($attr[self::ATTR_SN])) {
                        $principal->sn = $attr[self::ATTR_SN][0];
                }
                if (isset($attr[self::ATTR_UID])) {
                        $principal->uid = $attr[self::ATTR_UID][0];
                }

                return $principal;
        }

        /**
         * Get user model.
         * @param array $attr The attributes array.
         * @return User
         */
        public function getUser($attr)
        {
                // 
                // The assurance and affiliation data is array, all other should
                // be normalized as single values.
                // 
                foreach (array_keys($attr) as $key) {
                        if ($key == self::ATTR_ASSURANCE ||
                            $key == self::ATTR_AFFIL) {
                                continue;
                        } else {
                                $attr[$key] = $attr[$key][0];
                        }
                }

                $attrmap = array_combine(array_keys($attr), array_keys($attr));
                $attrmap = array_replace($attrmap, self::$attrmap);

                $user = new User();
                $user->assign($attr, $attrmap);

                return $user;
        }

}
