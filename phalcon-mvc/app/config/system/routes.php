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

$router->add(
    "/", array(
        "controller" => "index",
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/:controller", array(
        "controller" => 1,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/:controller/:action", array(
        "controller" => 1,
        "action"     => 2,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/:controller/:action/:params", array(
        "controller" => 1,
        "action"     => 2,
        "params"     => 3,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/about", array(
        "controller" => "index",
        "action"     => "about",
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
  ); */

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

$router->add(
    "/exam/:int", array(
        "controller" => "exam",
        "action"     => "instruction",
        "examId"     => 1,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/result/:int/:action", array(
        "controller" => "result",
        "action"     => 2,
        "examId"     => 1,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

$router->add(
    "/result/:int/:action/:int", array(
        "controller" => "result",
        "action"     => 2,
        "examId"     => 1,
        "studentId"  => 3,
        "namespace"  => "OpenExam\Controllers\Gui"
    )
);

//@ToDO: combine following rules to make them more generic
$router->add(
    "/exam/{examId}/question/{questId}", array(
        "controller" => "question",
        "action"     => "view",
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

$router->mount(
    new PrefixRoute(array(
        "prefix"    => "/utility",
        "namespace" => "OpenExam\Controllers\Utility"
    ))
);

/**
 * Route SOAP and WSDL requests:
 */
$router->mount(
    new PrefixRoute(array(
        "prefix"    => "/soap",
        "namespace" => "OpenExam\Controllers\Service\Soap"
    ))
);

/**
 * Route AJAX requests (e.g. "/ajax/core/student/exam/read").
 */
$group = $router->mount(
    new PrefixRoute(array(
        "prefix"    => "/ajax",
        "namespace" => "OpenExam\Controllers\Service\Ajax"
    ))
);
$group->addPost(
    "/ajax/core/{role}/{model}/:action", array(
        "controller" => "core",
        "action"     => 3,
        "namespace"  => "OpenExam\Controllers\Service\Ajax"
    )
);
$group->addPost(
    "/ajax/core/:action", array(
        "controller" => "core",
        "action"     => 1,
        "namespace"  => "OpenExam\Controllers\Service\Ajax"
    )
);

/**
 * Route REST requests.
 */
$group = $router->mount(
    new PrefixRoute(array(
        "prefix"    => "/rest",
        "namespace" => "OpenExam\Controllers\Service\Rest"
    ))
);
$group->add(
    "/rest/core/{role}/{target}/:params", array(
        "controller" => "core",
        "action"     => "index",
        "namespace"  => "OpenExam\Controllers\Service\Rest",
        "params"     => 3
    )
);
$group->add(
    "/rest/core/{role}/search/{target}", array(
        "controller" => "core",
        "action"     => "search",
        "namespace"  => "OpenExam\Controllers\Service\Rest"
    )
)->setHttpMethods('POST');

$router->removeExtraSlashes(true);
$router->handle();

return $router;
