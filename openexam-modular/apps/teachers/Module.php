<?php

namespace OpenExam\Teachers;

class Module
{

	public function registerAutoloaders()
	{
		$loader = new \Phalcon\Loader();

		$loader->registerNamespaces(array(
			'OpenExam\Teachers\Controllers' => __DIR__ . '/controllers/',
			'OpenExam\Models'               => __DIR__ . '/../_common/models/',
			'OpenExam\Components\Palette'   => __DIR__ . '/../_common/library/Palette/',
		));

		$loader->register();
	}

	public function registerServices($di)
	{
		/**
		 * Read configuration
		 */
		$config = require __DIR__."/config/config.php";

		/**
		 * Setting up the view component
		 */
		$di->set('view', function() {

			$view = new \Phalcon\Mvc\View();
			$view->setLayoutsDir('./../../_common/layouts/');
			$view->setViewsDir(__DIR__.'/views/');
			$view->setTemplateBefore('main');

			return $view;
		});
		
	}

}