<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
