<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Sockets.php
// Created: 2016-05-29 16:33:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector\Apache;

use DirectoryIterator;
use OpenExam\Library\Console\Command;
use OpenExam\Library\Monitor\Exception;

/**
 * Sockets used by Apache.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Sockets
{

        /**
         * The command to get list of PIDs.
         */
        const COMMAND = "ps -U %s -o pid=";

        /**
         * @var Command
         */
        private $_command;

        /**
         * Constructor.
         * @param string $user The apache server user.
         */
        public function __construct($user = 'apache')
        {
                $command = sprintf(self::COMMAND, $user);
                $this->_command = new Command($command);
        }

        /**
         * Get socket status.
         * 
         * The socket information is gathered from the /proc file system by
         * scanning all file descriptors. The returned result is summarized
         * for all Apache processes.
         * 
         * The returned result has this format:
         * 
         * <code>
         * array(
         *      'socket' => int,
         *      'mutex'  => int,
         *      'event'  => int,
         *      'pipe'   => int,
         *      'file'   => int,
         *      'total'  => int
         * )
         * </code>
         * 
         * @return array
         * @throws Exception
         */
        public function getStatus()
        {
                if (!$this->_command->execute()) {
                        throw new Exception($this->_command->getError());
                }

                $result = array(
                        'socket' => 0,
                        'mutex'  => 0,
                        'event'  => 0,
                        'pipe'   => 0,
                        'file'   => 0,
                        'total'  => 0
                );

                foreach ($this->_command->getOutput() as $pid) {
                        try {
                                $directory = sprintf("/proc/%d/fd", $pid);

                                if (!($iterator = new DirectoryIterator($directory))) {
                                        continue;
                                }
                                if (!$iterator->isReadable()) {
                                        continue;
                                }

                                foreach ($iterator as $fileinfo) {
                                        if (!$fileinfo->valid()) {
                                                continue;
                                        }
                                        if ($fileinfo->isDot()) {
                                                continue;
                                        }
                                        if (!$fileinfo->isLink()) {
                                                continue;
                                        }

                                        $target = $fileinfo->getLinkTarget();

                                        if (strstr($target, 'socket')) {
                                                $result['socket'] ++;
                                        } elseif (strstr($target, 'mutex')) {
                                                $result['mutex'] ++;
                                        } elseif (strstr($target, 'event')) {
                                                $result['event'] ++;
                                        } elseif (strstr($target, 'pipe')) {
                                                $result['pipe'] ++;
                                        } else {
                                                $result['file'] ++;
                                        }

                                        $result['total'] ++;
                                }
                        } catch (\Exception $exception) {
                                trigger_error($exception->getMessage());
                        }
                }

                return $result;
        }

}
