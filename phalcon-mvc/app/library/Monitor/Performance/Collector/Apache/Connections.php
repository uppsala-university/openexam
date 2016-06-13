<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Connections.php
// Created: 2016-05-29 18:38:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector\Apache;

use OpenExam\Library\Console\Command;
use OpenExam\Library\Monitor\Exception;
use OpenExam\Library\Monitor\Performance\SocketState;

/**
 * Connection statistics.
 * 
 * The connection statistics are read from the ss command. Remember to insert
 * the tcp-diag kernel module to accelerate lookup by refraining from parsing
 * information from /proc.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Connections
{

        /**
         * The command to get socket connection statistics.
         */
        const COMMAND = "ss -tn state all '( sport = :http or sport = :https )' | awk -- '{print $1 \"\t\" $2 \"\t\" $3}'";

        /**
         * @var Command
         */
        private $_command;

        /**
         * Constructor.
         */
        public function __construct()
        {
                $this->_command = new Command(self::COMMAND);
                $this->_command->setEnvironment(array("PATH", "/bin:/sbin:/usr/bin:/usr/sbin"));
        }

        /**
         * Get socket state data.
         * 
         * The returned data has this format, where state is number of sockets
         * in various socket states and queue contains total number of bytes
         * pending for peer (send) or for reading (recv):
         * 
         * <code>
         * array(
         *      'state' => array(
         *              'established' => int,
         *              'syn-sent'    => int,
         *               ...
         *      ),
         *      'queue' => array(
         *              'send-bytes' => int,
         *              'recv-bytes' => int
         *      )
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
                        'state' => array(
                                'established' => 0,
                                'syn-sent'    => 0,
                                'syn-recv'    => 0,
                                'fin-wait-1'  => 0,
                                'fin-wait-2'  => 0,
                                'time-wait'   => 0,
                                'closed'      => 0,
                                'close-wait'  => 0,
                                'last-ack'    => 0,
                                'listening'   => 0,
                                'closing'     => 0
                        ), 'queue' => array(
                                'send-bytes' => 0,
                                'recv-bytes' => 0
                        )
                );

                $states = SocketState::compat();

                foreach ($this->_command->getOutput() as $line) {
                        list($state, $recv, $send) = explode("\t", $line);

                        if (isset($states[$state])) {
                                $result['state'][$states[$state]] ++;
                        }
                        if (!is_numeric($recv) || !is_numeric($send)) {
                                continue;
                        }
                        if ($recv != 0) {
                                $result['queue']['recv-bytes'] += $recv;
                        }
                        if ($send != 0) {
                                $result['queue']['send-bytes'] += $send;
                        }
                }

                return $result;
        }

}
