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
// File:    MainTask.php
// Created: 2014-09-08 20:41:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

use Phalcon\CLI\Task as PhalconTask;

/**
 * The main task.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class MainTask extends PhalconTask implements TaskInterface
{

        /**
         * Default action.
         */
        public function indexAction()
        {
                $this->helpAction();
        }

        /**
         * Show usage on stdout.
         * @param array $usage
         */
        protected static function showUsage(array $usage)
        {
                if (isset($usage['header'])) {
                        printf("%s\n\n", $usage['header']);
                }
                if (isset($usage['usage'])) {
                        printf("Usage:\n");
                        foreach ($usage['usage'] as $val) {
                                printf("  %s\n", $val);
                        }
                        printf("\n");
                }
                if (isset($usage['options'])) {
                        printf("Options:\n");
                        foreach ($usage['options'] as $key => $val) {
                                printf("  %-20s: %s\n", $key, $val);
                        }
                        printf("\n");
                }
                if (isset($usage['aliases'])) {
                        printf("Aliases:\n");
                        foreach ($usage['aliases'] as $key => $val) {
                                printf("  %-20s: %s\n", $key, $val);
                        }
                        printf("\n");
                }
                if (isset($usage['examples'])) {
                        printf("Examples:\n");
                        foreach ($usage['examples'] as $data) {
                                printf("  # %s:\n", $data['descr']);
                                printf("  %s\n", $data['command']);
                                printf("\n");
                        }
                        printf("\n");
                }
        }

        /**
         * Get task usage information.
         * @return array
         */
        public static function getUsage()
        {
                return array('action' => '--main');
        }

        public function helpAction()
        {
                printf("No default action defined, see --help.\n");
        }

}
