<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    HelpTask.php
// Created: 2014-09-09 05:44:47
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

/**
 * Description of HelpTask
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class HelpTask extends MainTask implements TaskInterface
{

        /**
         * Display usage information.
         */
        public function indexAction()
        {
                $this->helpAction();
        }

        public static function getUsage()
        {
                $usage = array(
                        'header'   => 'OpenExam management utility.',
                        'action'   => '--help',
                        'examples' => array(
                                array(
                                        'descr'   => 'Show help specific for the database task',
                                        'command' => '--database --help'
                                )
                        )
                );

                foreach (array(
                    DatabaseTask::getUsage(),
                    ModelTask::getUsage(),
                    GettextTask::getUsage(),
                    SoapServiceTask::getUsage(),
                    RenderTask::getUsage(),
                    CacheTask::getUsage()
                ) as $task) {
                        foreach ($task['usage'] as $val) {
                                $usage['usage'][] = sprintf("%s %s", $task['action'], $val);
                        }
                        $usage['usage'][] = "";
                }

                return $usage;
        }

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

}
