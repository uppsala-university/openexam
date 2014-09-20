<?php

namespace OpenExam\Models;

/**
 * The media model.
 * 
 * Represent meta data for an uploaded media file. Can be used as the registry 
 * for the media library. The media library contains multimedia files as 
 * video, images or audio, but also resource files as equation or formula
 * collections etc.
 * 
 * The uploaded file has different scopes (visibility). Scope is implicit
 * defined from property values within the model object:
 * 
 *   $exam_id == 0 && $inst == null       : Global
 *   $exam_id == 0 && $inst != null       : Institution or group.
 *   $exam_id != 0                        : Exam
 * 
 * This model provides the scope property that can be used to determine the
 * scope for a model object.
 * 
 * @property Exam $exam The related exam.
 * @property-read string $scope Get media file scope.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Media extends ModelBase
{

        /**
         * Global scope.
         */
        const scope_global = 'global';
        /**
         * Group scope.
         */
        const scope_group = 'inst';
        /**
         * Exam scope.
         */
        const scope_exam = 'exam';

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
         * The media name.
         * @var string
         */
        public $name;
        /**
         * Description of the media file.
         * @var string
         */
        public $description;
        /**
         * The relative path of the media file.
         * @var string
         */
        public $path;
        /**
         * The major MIME type, e.g. video or autdio.
         * @var string
         */
        public $mime;
        /**
         * The minor MIME type, e.g. pdf or ms-word.
         * @var string
         */
        public $type;
        /**
         * The institution or group name.
         * @var string
         */
        public $inst;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;

        public function initialize()
        {
                parent::initialize();
                $this->belongsTo("exam_id", "OpenExam\Models\Exam", "id", array("foreignKey" => true, "alias" => 'Exam'));
        }

        public function __get($property)
        {
                if ($property == 'scope') {
                        if ($this->exam_id != 0) {
                                return self::scope_exam;
                        } elseif ($this->inst != null) {
                                return self::scope_group;
                        } else {
                                return self::scope_global;
                        }
                } else {
                        parent::__get($property);
                }
        }

        public function getSource()
        {
                return 'medias';
        }

}
