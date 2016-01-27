<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Computer.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Behavior\Timestampable;

/**
 * The computer model.
 * 
 * Represent an computer that is accessing an exam (only for student).
 * 
 * @property Lock[] $locks The related lock (if any).
 * @property Room $room The related room (if any).
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

        protected function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Lock', 'computer_id', array(
                        'alias' => 'locks'
                ));
                $this->belongsTo('room_id', 'OpenExam\Models\Room', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'room'
                ));

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'updated',
                                'format' => 'Y-m-d H:i:s'
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field'  => 'updated',
                                'format' => 'Y-m-d H:i:s'
                        )
                )));

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'created',
                                'format' => 'Y-m-d H:i:s'
                        )
                )));
        }

        public function getSource()
        {
                return 'computers';
        }

}
