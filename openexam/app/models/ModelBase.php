<?php

namespace OpenExam\Models;

class ModelBase extends ModelBase
{

        public function initialize()
        {
                parent::initialize();
                $this->setReadConnectionService('dbread');
                $this->setWriteConnectionService('dbwrite');
        }

}
