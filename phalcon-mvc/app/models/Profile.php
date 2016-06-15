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
                        'request' => 'request',
                        'name'    => 'name',
                        'peak'    => 'peak',
                        'time'    => 'time',
                        'data'    => 'data'
                );
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
