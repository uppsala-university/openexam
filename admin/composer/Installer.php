<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Composer.php
// Created: 2014-09-13 00:39:37
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Composer;

use Composer\Script\Event;

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
        public static function postPackageInstall(Event $event)
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
        public static function postPackageUpdate(Event $event)
        {
                $package = $event->getOperation()->getInitialPackage();

                if (array_key_exists($package->getName(), self::$packages)) {
                        $handler = self::$packages[$package->getName()];
                        $installer = new $handler($package);
                        $installer->update();
                }
        }

}
