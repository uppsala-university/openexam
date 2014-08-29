<?php

namespace OpenExam\Models;

class ModelBase extends \Phalcon\Mvc\Model
{

        public function initialize()
        {
                $this->setReadConnectionService('dbread');
                $this->setWriteConnectionService('dbwrite');
        }

        /**
         * Get model resource name.
         * @return string
         */
        public function getName()
        {
                return lcfirst(substr(strrchr(get_class($this), "\\"), 1));
        }

}
