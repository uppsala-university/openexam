<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenStudent project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Student.php
// Created: 2017-10-20 14:44:43
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Guard;

use OpenExam\Library\Model\Exception;
use Phalcon\Mvc\Model;

/**
 * Student model guard.
 * 
 * Prevent use of various methods known to be problematic. Defined
 * as a trait to be included in various models.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
trait Student
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
        public static function findFirstByStudentId($parameters = null)
        {
                throw new Exception("Use Student::findFirst(\"student_id = '...'\") instead as magic property calls involving underscore properties are known to cause problem.");
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
        public static function findByStudentId($parameters = null)
        {
                throw new Exception("Use Student::find(\"student_id = '...'\") instead as magic property calls involving underscore properties are known to cause problem.");
        }

}
