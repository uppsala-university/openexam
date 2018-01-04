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
// File:    Translate.php
// Created: 2014-09-19 12:13:37
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate;

use Phalcon\Translate\AdapterInterface;

/**
 * Translation service (I18N).
 * Interface for runtime translation service.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface Translate extends AdapterInterface
{

        /**
         * Singularis.
         * 
         * @param string $msgid Singularis form.
         * @param array $params Substitution parameters.
         * @return string
         */
        function _($msgid, $params = null);

        /**
         * Singularis.
         * 
         * @param string $msgid Singularis form.
         * @param array $params Substitution parameters.
         * @return string
         */
        function gettext($msgid, $params = null);

        /**
         * Pluralis.
         * 
         * @param string $msgid1 Singularis form.
         * @param string $msgid2 Pluralis form.
         * @param int $count The form selector.
         * @param array $params Substitution parameters.
         * @return string
         */
        function ngettext($msgid1, $msgid2, $count, $params = null);
}
