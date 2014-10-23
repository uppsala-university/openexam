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
$router->setDefaultNamespace("OpenExam\Controllers");

/**
 * URL prefix to namespace mapper.
 */
class PrefixRoute extends Phalcon\Mvc\Router\Group
{

        public function __construct($config = array())
        {
                $this->setPrefix($config['prefix']);
                $this->add("/:controller", array(
                        "controller" => 1,
                        "action"     => "index",
                        "namespace"  => $config['namespace']
                    )
                );
                $this->add("/:controller/:action/:params", array(
                        "controller" => 1,
                        "action"     => 2,
						"params"     => 3,
                        "namespace"  => $config['namespace']
                    )
                );
        }

}

/**
 * Route Gui - Public pages (e.g. "/index")
 */
$router->add(
    "/", array(
        "controller" => "public",
        "action"     => "index",
        "namespace"  => "OpenExam\Controllers\Gui",
    )
);


$router->add(
    "/:action", array(
        "controller" => "public",
        "action"     => 1,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

/**
 * Route Gui - private pages
 */
$router->add(
    "/exam/:action/:params", array(
        "controller" => "exam",
        "action"     => 1,
	"params"     => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

/*
$router->add(
    "/exam/:int/view/?(.*)?", array(
        "controller" => "exam",
        "action"     => "view",
        "examId"     => 1,
        "questId"    => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);*/


$router->add(
    "/question/:action/:params", array(
        "controller" => "question",
        "action"     => 1,
        "params"     => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);


$router->add(
    "/media/:action", array(
        "controller" => "media",
        "action"     => 1,
        "namespace"  => "OpenExam\Controllers\Utility"
    )
);
$router->mount(
    new PrefixRoute(array(
        "prefix"    => "/",
        "namespace" => "OpenExam\Controllers\Gui"
    ))
);
$router->mount(
    new PrefixRoute(array(
        "prefix"    => "/test",
        "namespace" => "OpenExam\Controllers\Test"
    ))
);
$router->mount(
    new PrefixRoute(array(
        "prefix"    => "/utility",
        "namespace" => "OpenExam\Controllers\Utility"
    ))
);
$router->add(
    "/exam/:int", array (
        "controller" => "exam",
        "action"     => "instruction",
        "examId"     => 1,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

//@ToDO: combine following rules to make them more generic
$router->add(
    "/exam/:int/question/:int", array(
        "controller" => "question",
        "action"     => "view",
        "examId"     => 1,
        "questId"    => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/exam/:int/correction/:params", array(
        "controller" => "question",
        "action"     => "correction",
        "examId"     => 1,
        "questId"    => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

/**
 * Route Authentication pages (e.g. "/auth/*")
 */
$router->add(
    "/auth/:action/?(.*)?", array(
        "controller" => "auth",
        "action"     => 1,
        "authMethod" => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);


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
 * Route AJAX requests (e.g. "/core/ajax/student/exam/read").
 */
$router->add(
    "/core/ajax", array(
        "controller" => "ajax",
        "action"     => "api",
        "namespace"  => "OpenExam\Controllers\Core"
    )
);
$router->add(
    "/core/ajax/{role}/{model}/{task}", array(
        "controller" => "ajax",
        "namespace"  => "OpenExam\Controllers\Core"
    )
)->setHttpMethods("POST");

/**
 * Route REST requests.
 */
$router->add(
    "/core/rest", array(
        "controller" => "rest",
        "action"     => "api",
        "namespace"  => "OpenExam\Controllers\Core"
    )
);
$router->add(
    "/core/rest/{role}/{target}/:params", array(
        "controller" => "rest",
        "action"     => "index",
        "namespace"  => "OpenExam\Controllers\Core",
        "params"     => 3
    )
);
$router->add(
    "/core/rest/{role}/search/{target}", array(
        "controller" => "rest",
        "action"     => "search",
        "namespace"  => "OpenExam\Controllers\Core"
    )
)->setHttpMethods('POST');

$router->removeExtraSlashes(true);

$router->handle();

return $router;
