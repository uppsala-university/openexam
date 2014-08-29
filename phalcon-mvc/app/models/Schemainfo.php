<?php

namespace OpenExam\Models;

class Schemainfo extends ModelBase
{

        /**
         *
         * @var integer
         */
        public $major;
        /**
         *
         * @var integer
         */
        public $minor;

        public function getSource()
        {
                return 'schemainfo';
        }

}
