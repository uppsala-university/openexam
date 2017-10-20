<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenCorrector project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Corrector.php
// Created: 2017-10-20 14:53:47
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Guard;

use OpenExam\Library\Model\Exception;
use Phalcon\Mvc\Model;

/**
 * Corrector model guard.
 * 
 * Prevent use of various methods known to be problematic. Defined
 * as a trait to be included in various models.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
trait Corrector
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
        public static function findFirstByCorrectorId($parameters = null)
        {
                throw new Exception("Use Corrector::findFirst(\"corrector_id = '...'\") instead as magic property calls involving underscore properties are known to cause problem.");
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
        public static function findByCorrectorId($parameters = null)
        {
                throw new Exception("Use Corrector::find(\"corrector_id = '...'\") instead as magic property calls involving underscore properties are known to cause problem.");
        }

}
