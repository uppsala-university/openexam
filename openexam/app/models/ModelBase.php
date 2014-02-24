<?php

namespace OpenExam\Models;

class ModelBase extends \Phalcon\Mvc\Model
{

        public function initialize()
        {
                $this->setReadConnectionService('dbread');
                $this->setWriteConnectionService('dbwrite');
        }

}
