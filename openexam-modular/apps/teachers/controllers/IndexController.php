<?php

namespace OpenExam\Teachers\Controllers;
use ControllerBase;

class IndexController extends ControllerBase
{

	public function indexAction()
	{

		$offset = mt_rand(0, 1000);
		$key = 'index'.$offset;

		$exists = $this->view->getCache()->exists($key);
		if (!$exists) {

			$this->view->setVar('data', "data_to_be_sent_to_view");

		}

		$this->view->cache(array("key" => $key));
	}

}

