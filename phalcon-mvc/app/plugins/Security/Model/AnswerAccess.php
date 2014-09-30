<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AnswerAccess.php
// Created: 2014-09-30 14:47:07
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Models\Answer;

/**
 * Access control for the Answer model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AnswerAccess extends ObjectAccess
{

        /**
         * Behavour hook.
         * @param string $event
         * @param Answer $model
         * @param User $user The peer object.
         * @param User $user The peer object.
         */
        public function notify($event, $model, $user)
        {
                printf("%s: event=%s, model=%s, user=%s\n", __METHOD__, $event, $model->getName(),$user->getPrincipalName());

        }

}
