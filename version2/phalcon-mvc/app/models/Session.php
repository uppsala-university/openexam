<?php

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

        public function getSource()
        {
                return 'sessions';
        }

}
