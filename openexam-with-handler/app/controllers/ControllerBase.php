<?php

namespace OpenExam\Controllers;

/**
 * Base controller
 *
 */
class ControllerBase extends \Phalcon\Mvc\Controller
{

        protected function initialize()
        {
                \Phalcon\Tag::prependTitle('Uppsala Unuiversitet | ');
        }

        protected function forward($uri)
        {
                $uriParts = explode('/', $uri);
                return $this->dispatcher->forward(
                        array(
                                'controller' => $uriParts[0],
                                'action'     => $uriParts[1]
                        )
                );
        }

}
