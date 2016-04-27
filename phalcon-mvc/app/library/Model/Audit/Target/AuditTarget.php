<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuditTarget.php
// Created: 2016-04-26 22:27:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit\Target;

/**
 * Audit target interface.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface AuditTarget
{

        /**
         * Save model changes.
         * @param array $changes The model changes.
         * @return int The number of bytes written.
         */
        function write($changes);
}
