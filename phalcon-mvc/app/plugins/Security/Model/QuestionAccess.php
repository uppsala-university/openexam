<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    QuestionAccess.php
// Created: 2014-09-30 14:52:17
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Model;

use OpenExam\Models\Question;

/**
 * Access control for the Question model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class QuestionAccess extends ObjectAccess
{

        /**
         * Behavour hook.
         * @param string $event
         * @param Question $model
         * @param User $user The peer object.
         */
        public function notify($event, $model, $user)
        {
                printf("%s: event=%s, model=%s, user=%s\n", __METHOD__, $event, $model->getName(),$user->getPrincipalName());
        }

}
