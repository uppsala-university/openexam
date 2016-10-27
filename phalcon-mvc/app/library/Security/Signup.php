<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Signup.php
// Created: 2015-03-13 17:26:08
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security;

/**
 * Interface for signup handler classes.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface Signup
{

        /**
         * Set target user for all operations.
         * @param string $user The user principal name.
         */
        function setUser($user);

        /**
         * Return true if requested signup is enabled in config.
         * @return boolean
         */
        function isEnabled();

        /**
         * Return true if this signup (for teacher or student) has already
         * been applied.
         * @return boolean
         */
        function isApplied();

        /**
         * Get all exams available for assignment.
         * @return array
         */
        function getExams();

        /**
         * Assign exam to target user.
         * @param int $index The exam ID.
         */
        function assign($index);
}
