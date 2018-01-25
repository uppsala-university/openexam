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
// File:    Principal.php
// Created: 2014-10-22 11:55:00
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Catalog;

use OpenExam\Library\Catalog\Attribute\Normalizer\PersonalNumber;

/**
 * The user principal class.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Principal
{

        /**
         * The principal name attribute.
         */
        const ATTR_PN = 'principal';
        /**
         * The user affiliation attribute.
         */
        const ATTR_AFFIL = 'affiliation';
        /**
         * The user assurance attribute.
         */
        const ATTR_ASSUR = 'assurance';
        /**
         * The UID attribute.
         */
        const ATTR_UID = 'uid';
        /**
         * The name attribute.
         */
        const ATTR_NAME = 'name';
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
        const ATTR_PNR = 'pnr';
        /**
         * The expires date attribute.
         */
        const ATTR_EXPIRES = 'expires';
        /**
         * All attributes.
         */
        const ATTR_ALL = '*';
        /**
         * Security classing. Principal can be shown public.
         */
        const ACCESS_PUBLIC = 'public';
        /**
         * Security classing. Principal has hidden access attribute and 
         * should not be displayed unrestricted.
         */
        const ACCESS_HIDDEN = 'hidden';
        /**
         * Security classing. Principal has proteced access attribute and 
         * should not be displayed unrestricted.
         */
        const ACCESS_PROTECT = 'protected';

        /**
         * The principal name.
         * @var string 
         */
        public $principal;
        /**
         * The user affiliations.
         * @var array 
         */
        public $affiliation = array();
        /**
         * The UID.
         * @var string 
         */
        public $uid;
        /**
         * The common name (display name).
         * @var string 
         */
        public $name;
        /**
         * The sirname (last name).
         * @var string 
         */
        public $sn;
        /**
         * The given name (first name).
         * @var string 
         */
        public $gn;
        /**
         * The personal number.
         * @var string 
         */
        public $pnr;
        /**
         * The expires date.
         * @var string 
         */
        public $expires;
        /**
         * Email addresses.
         * @var array 
         */
        public $mail = array();
        /**
         * The user principal security classification.
         * @var int 
         */
        public $protection;
        /**
         * All attributes (including service specific).
         * @var array 
         */
        public $attr;

        /**
         * Constructor.
         */
        public function __construct()
        {
                $this->protection = false;
        }

        /**
         * Normalize user principal data.
         */
        public function normalize()
        {
                if (!empty($this->pnr)) {
                        $persnr = new PersonalNumber($this->pnr);
                        $this->pnr = $persnr->getNormalized();
                }
                if (!empty($this->gn)) {
                        $this->gn = ucwords(strtolower($this->gn));
                }
                if (!empty($this->sn)) {
                        $this->sn = ucwords(strtolower($this->sn));
                }
                if (!empty($this->gn) && !empty($this->sn)) {
                        $this->name = sprintf("%s %s", $this->gn, $this->sn);
                }
        }

        /**
         * Generate UID (username) and principal name.
         * 
         * @param string|callable $format The username formatter.
         * @param string $domain The user domain.
         */
        public function generate($format, $domain = null)
        {
                if (is_string($format)) {
                        // 
                        // Replace swedish characters:
                        // 
                        $replace = array('å' => 'a', 'ä' => 'a', 'ö' => 'o');

                        // 
                        // Use sub string of first and last name:
                        // 
                        $gnd = strtr(mb_strtolower(mb_substr($this->gn, 0, 2)), $replace);
                        $snd = strtr(mb_strtolower(mb_substr($this->sn, 0, 2)), $replace);

                        // 
                        // Personal number might be empty:
                        // 
                        if (($pnd = substr($this->pnr, -4)) == false) {
                                $pnd = substr(sprintf("%s%s", ord($gnd[0]), ord($snd[0])), 0, 4);
                        }

                        // 
                        // Format UID based on sub strings:
                        // 
                        $this->uid = sprintf($format, $gnd, $snd, $pnd);
                }
                if (is_callable($format)) {
                        $this->uid = call_user_func($format, $this->gn, $this->sn, $this->pnr);
                }
                if (isset($domain)) {
                        $this->principal = sprintf("%s@%s", $this->uid, $domain);
                }
        }

        /**
         * Assign user principal attributes.
         * 
         * <code>
         * // 
         * // Set uid, sn and gn if not yet set.
         * // 
         * $principal->assign(array(
         *      'principal' => 'olle@example.com',
         *      'name'      => 'Olle Svensson'
         * ));
         * </code>
         * 
         * @param array $attrs The principal attributes.
         */
        public function assign($attrs)
        {
                foreach ($attrs as $attr => $val) {
                        if (property_exists($this, $attr)) {
                                $this->$attr = $val;
                        }
                }

                if (isset($this->principal) && !isset($this->uid)) {
                        $parts = explode("@", $this->principal);
                        $this->uid = $parts[0];
                }

                if (isset($this->name) && !isset($this->gn) && !isset($this->sn)) {
                        $parts = explode(" ", $this->name);
                        $this->gn = $parts[0];
                        $this->sn = $parts[1];
                }
        }

}
