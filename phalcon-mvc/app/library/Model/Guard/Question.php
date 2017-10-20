<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Question.php
// Created: 2017-10-20 14:40:57
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Guard;

use OpenExam\Library\Model\Exception;
use Phalcon\Mvc\Model;

/**
 * Question model guard.
 * 
 * Prevent use of various methods known to be problematic. Defined
 * as a trait to be included in various models.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
trait Question
{

        /**
         * Guard against bad model access.
         * 
         * @param array $parameters The query parameters.
         * @return Model
         * @throws Exception
         * 
         * @deprecated since 2.0.5
         */
        public static function findFirstByQuestionId($parameters = null)
        {
                throw new Exception("Use Question::findFirst(\"question_id = '...'\") instead as magic property calls involving underscore properties are known to cause problem.");
        }

        /**
         * Guard against bad model access.
         * 
         * @param array $parameters The query parameters.
         * @return mixed
         * @throws Exception
         * 
         * @deprecated since 2.0.5
         */
        public static function findByQuestionId($parameters = null)
        {
                throw new Exception("Use Question::find(\"question_id = '...'\") instead as magic property calls involving underscore properties are known to cause problem.");
        }

}
