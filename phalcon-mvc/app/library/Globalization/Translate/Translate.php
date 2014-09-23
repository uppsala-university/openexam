<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        function text($msgid, $params = null);

        /**
         * Pluralis.
         * 
         * @param string $msgid1 Singularis form.
         * @param string $msgid2 pluralis form.
         * @param int $count Form selector.
         * @param array $params Substitution parameters.
         * @return string
         */
        function textn($msgid1, $msgid2, $count, $params = null);
}
