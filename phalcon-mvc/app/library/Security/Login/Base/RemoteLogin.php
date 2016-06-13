<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
