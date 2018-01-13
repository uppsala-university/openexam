<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Render.php
// Created: 2017-12-07 00:44:35
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
use Phalcon\Validation;
use Phalcon\Validation\Validator\InclusionIn;

/**
 * The render queue model.
 *
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET)
 */
class Render extends ModelBase
{

        /**
         * Guard against problematic methods use.
         */
        use ExamModelGuard;

        /**
         * Result render job.
         */
        const TYPE_RESULT = 'result';
        /**
         * Archive render job.
         */
        const TYPE_ARCHIVE = 'archive';
        /**
         * Export render job.
         */
        const TYPE_EXPORT = 'export';
        /**
         * Extern render job.
         */
        const TYPE_EXTERN = 'extern';
        /**
         * Job status is missing.
         */
        const STATUS_MISSING = 'missing';
        /**
         * Job is queued waiting to be rendered.
         */
        const STATUS_QUEUED = 'queued';
        /**
         * Job is currently being rendered.
         */
        const STATUS_RENDER = 'render';
        /**
         * Job finished render successful.
         */
        const STATUS_FINISH = 'finish';
        /**
         * This job has failed.
         */
        const STATUS_FAILED = 'failed';

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * The queued datetime.
         * @var string 
         */
        public $queued;
        /**
         * The finished datetime.
         * @var string 
         */
        public $finish;
        /**
         * The job owner.
         * @var string 
         */
        public $user;
        /**
         * The render URL.
         * @var string 
         */
        public $url;
        /**
         * The result file.
         * @var string 
         */
        public $path;
        /**
         * PID of waiting process.
         * @var int 
         */
        public $wait;
        /**
         * Type of job.
         * @var string 
         */
        public $type;
        /**
         * Status of job.
         * @var string 
         */
        public $status;
        /**
         * Last job message (output, error or null).
         * @var string 
         */
        public $message;

        public function initialize()
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
                return 'render';
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
                        'queued'  => 'queued',
                        'finish'  => 'finish',
                        'user'    => 'user',
                        'url'     => 'url',
                        'path'    => 'path',
                        'wait'    => 'wait',
                        'type'    => 'type',
                        'status'  => 'status',
                        'message' => 'message'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        public function validation()
        {
                $validator = new Validation();

                $validator->add(
                    'status', new InclusionIn(
                    array(
                        'domain' => array(self::STATUS_FAILED, self::STATUS_FINISH, self::STATUS_MISSING, self::STATUS_QUEUED, self::STATUS_RENDER)
                    )
                ));
                $validator->add(
                    'type', new InclusionIn(
                    array(
                        'domain' => array(self::TYPE_ARCHIVE, self::TYPE_EXPORT, self::TYPE_EXTERN, self::TYPE_RESULT)
                    )
                ));

                return $this->validate($validator);
        }

}
