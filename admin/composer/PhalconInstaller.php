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
                $this->symlink("ide/stubs", "api");
                // $this->patch("admin/patch/phalcon_devtools_fix_script_exception.diff");
                $this->patch("admin/patch/phalcon_devtools_2.0.13_autocomplete_for_project_services.diff");
        }

}
