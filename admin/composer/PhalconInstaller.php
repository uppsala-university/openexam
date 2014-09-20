<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PhalconInstaller.php
// Created: 2014-09-13 02:04:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Composer;

/**
 * Handle installation of Phalcon.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class PhalconInstaller extends PackageInstaller
{

        public function install($update = false)
        {
                $this->apply();
        }

        public function update()
        {
                $this->apply();
        }

        private function apply()
        {
                $this->symlink("ide/1.3.0", "api");
                $this->patch("admin/patch/phalcon_devtools_fix_script_exception.diff");
        }

}
