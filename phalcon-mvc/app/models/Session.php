<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Session.php
// Created: 2014-09-20 13:00:59
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models;

/**
 * The session model.
 * 
 * This model represents logon sessions.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Session extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The session ID.
         * @var string
         */
        public $session_id;
        /**
         * The session data.
         * @var string
         */
        public $data;
        /**
         * Timestamp.
         * @var integer
         */
        public $created;
        /**
         * Timestamp.
         * @var integer
         */
        public $updated;

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'sessions';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'         => 'id',
                        'session_id' => 'session_id',
                        'data'       => 'data',
                        'created'    => 'created',
                        'updated'    => 'updated'
                );
        }

}
