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

/*
 * Created: 2014-09-13 00:39:37
 * File:    Composer.php
 * 
 * Author:  Anders Lövgren (QNET/BMC CompDept)
 */

namespace OpenExam\Composer;

use Composer\Installer\PackageEvent;

/**
 * Handles install/update hooks for Composer.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Installer
{

        /**
         * Package to installer class map.
         * @var array 
         */
        private static $packages = array(
                'phalcon/devtools' => 'OpenExam\Composer\PhalconInstaller'
        );

        /**
         * Package install hook.
         * @param \Composer\Script\Event $event
         */
        public static function postPackageInstall(PackageEvent $event)
        {
                $package = $event->getOperation()->getPackage();

                if (array_key_exists($package->getName(), self::$packages)) {
                        $handler = self::$packages[$package->getName()];
                        $installer = new $handler($package);
                        $installer->install();
                }
        }

        /**
         * Package update hook.
         * @param \Composer\Script\Event $event
         */
        public static function postPackageUpdate(PackageEvent $event)
        {
                $package = $event->getOperation()->getInitialPackage();

                if (array_key_exists($package->getName(), self::$packages)) {
                        $handler = self::$packages[$package->getName()];
                        $installer = new $handler($package);
                        $installer->update();
                }
        }

}
