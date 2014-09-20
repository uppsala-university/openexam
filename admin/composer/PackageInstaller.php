<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PackageInstaller.php
// Created: 2014-09-13 01:57:30
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Composer;

use Composer\Package\CompletePackageInterface;

/**
 * Composer package installer.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class PackageInstaller
{

        /**
         * Interface for the Composer package.
         * @var \Composer\Package\CompletePackageInterface 
         */
        protected $package;
        /**
         * The package name.
         * @var string 
         */
        protected $name;
        /**
         * The package version.
         * @var string 
         */
        protected $version;
        /**
         * The package destination directory.
         * @var string 
         */
        protected $destdir;

        /**
         * Constructor.
         * @param \Composer\Package\CompletePackageInterface $package
         */
        public function __construct(CompletePackageInterface $package)
        {
                $this->package = $package;
                $this->name = $package->getName();
                $this->version = $package->getVersion();
                $this->destdir = $package->getTargetDir();

                if (empty($this->destdir)) {
                        $this->destdir = sprintf("vendor/%s", $this->name);
                }
        }

        /**
         * Create symbolic link.
         * 
         * <code>
         * $this->symlink("ide/1.3.2", "api");
         * $this->symlink("docs", "docs/package", "vendor");
         * </code>
         * @param string $source The link source.
         * @param string $target The link target.
         * @param string $rootdir The link destination directory (default to $destdir).
         * @return boolean
         */
        protected function symlink($source, $target, $rootdir = null)
        {
                $cwd = getcwd();

                if (!isset($rootdir)) {
                        $rootdir = $this->destdir;
                }

                if (!chdir($rootdir)) {
                        fprintf(STDERR, "%s: Failed change working directory to %s\n", __METHOD__, $rootdir);
                        return false;
                }
                if (!file_exists($source)) {
                        fprintf(STDERR, "%s: The link source don't exist (%s)\n", __METHOD__, $source);
                        chdir($cwd);
                        return false;
                }
                if (!symlink($source, $target)) {
                        fprintf(STDERR, "%s: Failed create symlink (%s -> %s)\n", __METHOD__, $source, $target);
                        chdir($cwd);
                        return false;
                }

                chdir($cwd);
                
                printf("    Created symbolic link %s -> %s\n", $target, $source);
                return true;
        }

        /**
         * Apply unified diff/patch.
         * @param string $file The patch file.
         * @param string $rootdir The directory to apply patch from (defaults to vendor).
         * @return boolean
         */
        protected function patch($file, $rootdir = null)
        {
                $cwd = getcwd();

                $command = sprintf("patch -p0 -fi %s", $file);
                $output = "";
                $status = 0;

                if (isset($rootdir) && !chdir($rootdir)) {
                        fprintf(STDERR, "%s: Failed change working directory to %s\n", __METHOD__, $rootdir);
                        return false;
                }

                exec($command, $output, $status);
                if ($status != 0) {
                        fprintf(STDERR, "%s: Failed apply patch %s: %s\n", __METHOD__, basename($file), $output);
                        chdir($cwd);
                        return false;
                }

                chdir($cwd);
                
                printf("    Applied patch %s\n", $file);
                return true;
        }

        /**
         * Package install event hook.
         */
        public function install()
        {
                
        }

        /**
         * Package update event hook.
         */
        public function update()
        {
                
        }

}
