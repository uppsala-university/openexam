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
                    CacheTask::getUsage(),
                    ResultTask::getUsage(),
                    AuditTask::getUsage(),
                    StatisticsTask::getUsage(),
                    PerformanceTask::getUsage(),
                    DiagnosticsTask::getUsage(),
                    CatalogTask::getUsage(),
                    UserTask::getUsage(),
                    ArchiveTask::getUsage()
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
