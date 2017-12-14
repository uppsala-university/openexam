<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Render.php
// Created: 2017-12-07 00:44:35
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
use Phalcon\Mvc\Model\Validator\Inclusionin;

/**
 * The render queue model.
 *
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
        protected function validation()
        {
                $this->validate(new Inclusionin(
                    array(
                        'field'  => 'status',
                        'domain' => array(self::STATUS_FAILED, self::STATUS_FINISH, self::STATUS_MISSING, self::STATUS_QUEUED, self::STATUS_RENDER)
                    )
                ));
                $this->validate(new Inclusionin(
                    array(
                        'field'  => 'type',
                        'domain' => array(self::TYPE_ARCHIVE, self::TYPE_EXPORT, self::TYPE_EXTERN, self::TYPE_RESULT)
                    )
                ));

                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

}
