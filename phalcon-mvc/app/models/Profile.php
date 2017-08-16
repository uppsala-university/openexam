<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Profile.php
// Created: 2016-06-15 22:30:20
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models;

/**
 * Profile data model.
 * 
 * Having both a server and host might seem redundant at first glance, but is
 * required in more complex setups to differentiate real servers from each
 * other.
 * 
 * The reason behind is: 
 * 
 * When serving as part of a web cluster (i.e. under IPVS using a single shared 
 * public IP), the server and addr property will actually get their values from 
 * the request and its identical for all real servers. 
 * 
 * In this case, using the host property is the only way to separate them from 
 * each other that get its value from gethostname() that is mapped against the
 * system name from /etc/hosts
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Profile extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The datetime stamp.
         * @var string 
         */
        public $stamp;
        /**
         * The request URL.
         * @var string 
         */
        public $request;
        /**
         * The profile identity.
         * @var string 
         */
        public $name;
        /**
         * Peak memory usage (in bytes)
         * @var int 
         */
        public $peak;
        /**
         * Total elapsed time.
         * @var float 
         */
        public $time;
        /**
         * Server host (optional).
         * @var string 
         */
        public $host;
        /**
         * Server address (optional).
         * @var string 
         */
        public $addr;
        /**
         * Server name (optional).
         * @var string 
         */
        public $server;
        /**
         * The profile data (details).
         * @var data 
         */
        public $data;

        protected function initialize()
        {
                if ($this->getDI()->has('audit')) {
                        $audit = $this->getDI()->get('audit');
                        $model = $this->getResourceName();

                        if ($audit->hasConfig($model)) {
                                $audit->setDisabled($model);
                        }
                }

                parent::initialize();
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'profile';
        }

        /**
         * The table column map.
         */
        public function columnMap()
        {
                return array(
                        'id'      => 'id',
                        'stamp'   => 'stamp',
                        'request' => 'request',
                        'name'    => 'name',
                        'peak'    => 'peak',
                        'time'    => 'time',
                        'host'    => 'host',
                        'addr'    => 'addr',
                        'server'  => 'server',
                        'data'    => 'data'
                );
        }

        /**
         * Called before model is persisted.
         */
        protected function beforeValidation()
        {
                if (isset($this->time)) {
                        $this->time = str_replace(",", ".", $this->time);
                }
        }

        protected function beforeSave()
        {
                $this->data = serialize($this->data);
        }

        protected function afterSave()
        {
                $this->data = unserialize($this->data);
        }

        public function afterFetch()
        {
                $this->data = unserialize($this->data);
                parent::afterFetch();
        }

}
