<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Principal.php
// Created: 2014-10-22 11:55:00
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Catalog;

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
         * The UID attribute.
         */
        const ATTR_UID = 'uid';
        /**
         * The common name attribute.
         */
        const ATTR_CN = 'cn';
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
         * The UID.
         * @var string 
         */
        public $uid;
        /**
         * The common name (display name).
         * @var string 
         */
        public $cn;
        /**
         * The sirname (last name).
         * @var string 
         */
        public $sn;
        /**
         * The given name (first name).
         * @var type 
         */
        public $gn;
        /**
         * The personal number.
         * @var string 
         */
        public $pnr;
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

}
