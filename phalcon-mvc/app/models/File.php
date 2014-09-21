<?php

namespace OpenExam\Models;

/**
 * The file model.
 * 
 * Represent an file uploaded as part of an question answer.
 * 
 * @property Answer $answer The related answer.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class File extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The answer ID.
         * @var integer
         */
        public $answer_id;
        /**
         * The file name (descriptive).
         * @var string
         */
        public $name;
        /**
         * The file path.
         * @var string
         */
        public $path;
        /**
         * The MIME type (e.g. video).
         * @var string
         */
        public $type;
        /**
         * The MIME subtype (e.g. pdf).
         * @var string
         */
        public $subtype;

        protected function initialize()
        {
                parent::initialize();
                $this->belongsTo("answer_id", "OpenExam\Models\Answer", "id", array("foreignKey" => true, "alias" => 'Answer'));
        }

        public function getSource()
        {
                return 'files';
        }

}
