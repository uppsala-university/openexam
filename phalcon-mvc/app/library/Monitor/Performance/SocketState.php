<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SocketState.php
// Created: 2016-05-29 19:42:51
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance;

/**
 * Socket state class.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SocketState
{

        /**
         * The socket has an established connection.
         */
        const ESTABLISHED = 'established';
        /**
         * The socket is actively attempting to establish a connection.
         */
        const SYN_SENT = 'syn-sent';
        /**
         * A connection request has been received from the network.
         */
        const SYN_RECV = 'syn-recv';
        /**
         * The socket is closed, and the connection is shutting down.
         */
        const FIN_WAIT1 = 'fin-wait-1';
        /**
         * Connection is closed, and the socket is waiting for a shutdown from the remote end.
         */
        const FIN_WAIT2 = 'fin-wait-2';
        /**
         * The socket is waiting after close to handle packets still in the network.
         */
        const TIME_WAIT = 'time-wait';
        /**
         * The socket is not being used.
         */
        const CLOSE = 'unconnected';
        /**
         * The remote end has shut down, waiting for the socket to close.
         */
        const CLOSE_WAIT = 'close-wait';
        /**
         * The remote end has shut down, and the socket is closed. Waiting for acknowledgement.
         */
        const LAST_ACK = 'last-ack';
        /**
         * The socket is listening for incoming connections.
         */
        const LISTEN = 'listening';
        /**
         * Both sockets are shut down but we still don't have all our data sent.
         */
        const CLOSING = 'closing';
        /**
         * The state of the socket is unknown.
         */
        const UNKNOWN = 'unknown';

        /**
         * The socket state map (normal). This uses ordinary key names as
         * used by netstat.
         * 
         * @var array
         */
        private static $map = array(
                'ESTABLISHED' => self::ESTABLISHED,
                'CLOSE_WAIT'  => self::CLOSE_WAIT,
                'CLOSING'     => self::CLOSING,
                'FIN_WAIT1'   => self::FIN_WAIT1,
                'FIN_WAIT2'   => self::FIN_WAIT2,
                'LAST_ACK'    => self::LAST_ACK,
                'LISTEN'      => self::LISTEN,
                'SYN_RECV'    => self::SYN_RECV,
                'SYN_SENT'    => self::SYN_SENT,
                'TIME_WAIT'   => self::TIME_WAIT,
                'UNKNOWN'     => self::UNKNOWN,
                'CLOSE'       => self::CLOSE
        );
        /**
         * Alternative socket state map. Got name used by ss as keys.
         * 
         * @var array
         */
        private static $ssm = array(
                'ESTAB'      => self::ESTABLISHED,
                'SYN-SENT'   => self::SYN_SENT,
                'SYN-RECV'   => self::SYN_RECV,
                'FIN-WAIT-1' => self::FIN_WAIT1,
                'FIN-WAIT-2' => self::FIN_WAIT2,
                'TIME-WAIT'  => self::TIME_WAIT,
                'UNCONN'     => self::CLOSE,
                'CLOSE-WAIT' => self::CLOSE_WAIT,
                'LAST-ACK'   => self::LAST_ACK,
                'LISTEN'     => self::LISTEN,
                'CLOSING'    => self::CLOSING,
        );
        /**
         * The socket state map (reversed).
         * @var array
         */
        private static $rev = array();

        /**
         * Constructor.
         */
        public function __construct()
        {
                self::$rev = array_reverse(self::$map);
        }

        /**
         * Get standard state map.
         * 
         * The state map consist of mapping between standard kernel identities
         * for socket state (as reported by e.g. netstat) and lower case names
         * as used by socket state tools as ss.
         * 
         * <code>
         * array(
         *      'ESTABLISHED' => 'established',
         *      'FIN_WAIT2'   => 'fin-wait-2',
         *       ...
         * )
         * </code>
         * @return array
         */
        public static function standard()
        {
                return self::$map;
        }

        /**
         * Get compatible state map.
         * 
         * This function return the socket state map built using ss command
         * key names.
         * 
         * @return array
         */
        public static function compat()
        {
                return self::$ssm;
        }

        /**
         * Lookup lower case state.
         * 
         * @param string $state The upper case state.
         * @return string
         */
        public function strtolower($state)
        {
                if (isset(self::$map[$state])) {
                        return self::$map[$state];
                }
        }

        /**
         * Lookup upper case state.
         * 
         * @param string $state The lower case state.
         * @return string
         */
        public function strtoupper($state)
        {
                if (isset(self::$rev[$state])) {
                        return self::$rev[$state];
                }
        }

}
