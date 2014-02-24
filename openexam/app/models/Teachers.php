<?php

namespace OpenExam\Models;




class Teachers extends ModelBase
{

    /**
     *
     * @var integer
     */
    public $id;
     
    /**
     *
     * @var string
     */
    public $user;
     
    public function getSource()
    {
        return 'teachers';
    }

}
