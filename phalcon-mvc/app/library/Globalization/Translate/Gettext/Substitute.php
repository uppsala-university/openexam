<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Substitute.php
// Created: 2014-11-04 01:02:43
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

if (!function_exists('_')) {

        function _($msgid)
        {
                return vsprintf($msgid, func_get_args());
        }

        function ngettext($msgid1, $msgid2, $n)
        {
                return vsprintf($n < 2 ? $msgid1 : $msgid2, func_get_args());
        }

        function dngettext($domain, $msgid1, $msgid2, $n)
        {
                return ngettext($msgid1, $msgid2, $n);
        }

}
