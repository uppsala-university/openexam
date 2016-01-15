<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
