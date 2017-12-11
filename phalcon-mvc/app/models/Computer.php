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

use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Room as RoomModelGuard;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model\Validator\Uniqueness;

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
         * Guard against problematic methods use.
         */
        use RoomModelGuard;

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
                        'alias'    => 'locks',
                        'reusable' => true
                ));
                $this->belongsTo('room_id', 'OpenExam\Models\Room', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'room',
                        'reusable'   => true
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

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('hostname', 'ipaddr', 'password'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('hostname', 'ipaddr', 'password'),
                                'value' => null
                        )
                )));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'computers';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'       => 'id',
                        'room_id'  => 'room_id',
                        'hostname' => 'hostname',
                        'ipaddr'   => 'ipaddr',
                        'port'     => 'port',
                        'password' => 'password',
                        'created'  => 'created',
                        'updated'  => 'updated'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Uniqueness(
                    array(
                        'field'   => 'hostname',
                        'message' => "The hostname already exists"
                    )
                ));

                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

}
