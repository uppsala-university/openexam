<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UppdokService.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Backend;

use OpenExam\Library\Catalog\DirectoryServiceAdapter;

/**
 * UPPDOK directory service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class UppdokDirectoryService extends DirectoryServiceAdapter
{

        public function close()
        {
                // ignore, connection less state
        }

        public function open()
        {
                // ignore, connection less state
                return true;
        }

        public function connected()
        {
                // ignore, connection less state
                return true;
        }

}
