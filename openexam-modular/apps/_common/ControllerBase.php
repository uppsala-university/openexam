<?php

use Phalcon\Tag;

class ControllerBase extends \Phalcon\Mvc\Controller
{

	protected function initialize()
	{
		Tag::setTitle('OpenExam');
	}

}