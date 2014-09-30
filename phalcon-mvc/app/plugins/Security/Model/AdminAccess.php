<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Admin.php
// Created: 2014-09-29 14:09:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Models\Admin;

/**
 * Access control for the Admin model.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AdminAccess extends ObjectAccess
{

        /**
         * Behavour hook.
         * @param string $event
         * @param Admin $model
         */
        public function notify($event, $model)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event, $model->getName());
        }

}
