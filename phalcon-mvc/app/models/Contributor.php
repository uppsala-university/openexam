<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Contributor.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

/**
 * The contributor model.
 * 
 * Represents a user having the contributor role.
 * 
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Contributor extends Role
{

        /**
         * This object ID.
         * @var integer
         */
        public $id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;

        protected function initialize()
        {
                parent::initialize();

                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'exam',
                        'reusable'   => true
                ));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'contributors';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'      => 'id',
                        'exam_id' => 'exam_id',
                        'user'    => 'user'
                );
        }

        /**
         * Called after model is created.
         */
        protected function afterCreate()
        {
                parent::afterCreate();
                $this->exam->getStaff()->addRole($this);
        }

        /**
         * Called after model is deleted.
         */
        protected function afterDelete()
        {
                parent::afterDelete();
                $this->exam->getStaff()->removeRole($this);
        }

}
