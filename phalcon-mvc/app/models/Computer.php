<?php

namespace OpenExam\Models;

/**
 * The computer model.
 * 
 * Represent an computer that is accessing an exam (only for student).
 * 
 * @property Lock $Lock The related lock (if any).
 * @property Room $Room The related room (if any).
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Computer extends ModelBase
{

        /**
         * This object ID.
         * @var integer
         */
        public $id;
        /**
         * The room ID.
         * @var integer
         */
        public $room_id;
        /**
         * The fully qualified hostname.
         * @var string
         */
        public $hostname;
        /**
         * The IP-address (IPv4 or IPv6).
         * @var string
         */
        public $ipaddr;
        /**
         * The listening port on peer.
         * @var integer
         */
        public $port;
        /**
         * Password for the lockdown service on peer.
         * @var string
         */
        public $password;
        /**
         * The object create date/time.
         * @var string
         */
        public $created;
        /**
         * The object update date/time.
         * @var string
         */
        public $updated;

        public function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Lock', 'computer_id', array('alias' => 'Locks'));
                $this->belongsTo('room_id', 'OpenExam\Models\Room', 'id', array('foreignKey' => true, 'alias' => 'Room'));
        }

        /**
         * Called before model is created.
         */
        public function beforeCreate()
        {
                $this->created = date('Y-m-d H:i:s');
        }

        /**
         * Called before the model is updated.
         */
        public function beforeUpdate()
        {
                $this->updated = date('Y-m-d H:i:s');
        }

        public function getSource()
        {
                return 'computers';
        }

}
