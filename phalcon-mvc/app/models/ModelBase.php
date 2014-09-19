<?php

namespace OpenExam\Models;

/**
 * Base class for all models.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelBase extends \Phalcon\Mvc\Model
{

        protected function initialize()
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
                return strtolower(substr(strrchr(get_class($this), "\\"), 1));
        }

}
