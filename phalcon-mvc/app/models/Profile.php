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
         * @return array 
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
