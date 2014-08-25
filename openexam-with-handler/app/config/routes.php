<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    routes.php
// Created: 2014-08-20 11:30:56
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$router = new Phalcon\Mvc\Router();
$router->setDI($di);

/**
 * Route SOAP and WSDL requests:
 */
$router->add(
    "/core/soap/:action", array(
        "controller" => "wsdl",
        "action"     => 1,
        "namespace"  => "OpenExam\Controllers\Core"
    )
);
$router->add(
    "/core/soap", array(
        "controller" => "soap",
        "action"     => "index",
        "namespace"  => "OpenExam\Controllers\Core"
    )
)->setName('core-soap');

/**
 * Route AJAX requests (e.g. "/core/ajax/student/exam/read")
 */
$router->add(
    "/core/ajax", array(
        "controller" => "ajax",
        "action"     => "api",
        "namespace"  => "OpenExam\Controllers\Core",
    )
);
$router->add(
    "/core/ajax/{role}/{model}/{task}", array(
        "controller" => "ajax",
        "namespace"  => "OpenExam\Controllers\Core"
    )
)->setHttpMethods("POST");

$router->handle();

return $router;
