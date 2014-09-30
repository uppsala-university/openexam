<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ResourceAccess.php
// Created: 2014-09-30 14:52:39
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use Phalcon\Assets\Resource;

/**
 * Access control for the Resource model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResourceAccess extends ObjectAccess
{

        /**
         * Behavour hook.
         * @param string $event
         * @param Resource $model
         * @param User $user The peer object.
         */
        public function notify($event, $model, $user)
        {
                printf("%s: event=%s, model=%s, user=%s\n", __METHOD__, $event, $model->getName(),$user->getPrincipalName());
        }

}
