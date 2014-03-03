<?php

use Phalcon\Tag;

class Controller extends \Phalcon\Mvc\Controller
{

	protected function initialize()
	{
		Tag::setTitle('OpenExam');
	}

}