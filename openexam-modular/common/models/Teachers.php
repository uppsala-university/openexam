<?php

namespace OpenExam\Models;

use OpenExam\Components\Palette\Palette;

class Teachers extends \Phalcon\Mvc\Model
{

	public $id;

	public $user;

	public function getSource()
	{
		return 'teachers';
	}

	public function initialize()
	{
		//$this->belongsTo();
		//$this->hasMany();
	}

}
