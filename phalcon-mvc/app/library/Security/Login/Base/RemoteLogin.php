<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    RemoteLogin.php
// Created: 2014-09-10 15:34:56
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login\Base;

/**
 * Interface for remote login.
 * 
 * Login classes that implement this interface is not processing authentication
 * credentials local, but passes them to some other server for doing the actual
 * validation.
 * 
 * Virtualy all login classes will implement this interface. The exception is
 * classes doing e.g. host based authentication, using system databases or
 * handling token based logons.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface RemoteLogin
{

        /**
         * Get hostname of remote login server.
         * @return string
         */
        function hostname();

        /**
         * Get port of remote login server.
         * @return string
         */
        function port();

        /**
         * Get path (if applicable) of remote login server.
         * @return string
         */
        function path();
}
