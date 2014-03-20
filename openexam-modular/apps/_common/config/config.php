<?php

// Load Base controller and Models
require __DIR__ . "/../ControllerBase.php";

// Db config
return new \Phalcon\Config(array(
	'database' => array(
		'adapter'  => 'Mysql',
		'host'     => 'localhost',
		'username' => 'root',
		'password' => 'root',
		'name'     => 'openexam',
	)
));
