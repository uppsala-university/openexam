<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Bucket.php
// Created: 2016-11-14 02:09:53
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute\Storage;

/**
 * The bit bucket storage backend.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Bucket implements Backend
{

        public function delete($principal)
        {
                // ignore
        }

        public function exists($principal)
        {
                return false;
        }

        public function insert($user)
        {
                // ignore
        }

}
