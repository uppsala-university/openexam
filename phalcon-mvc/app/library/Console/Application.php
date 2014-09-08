<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Application.php
// Created: 2014-09-08 12:45:04
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Console;

/**
 * Command option scanner.
 */
class CommandOption
{

        public $key;
        public $val;

        public function __construct($args)
        {
                $match = array();
                if (preg_match("/--(.*?)=(.*)/", $args, $match)) {
                        $this->key = $match[1];
                        $this->val = $match[2];
                } elseif (preg_match("/--(.*)/", $args, $match)) {
                        $this->key = $match[1];
                } elseif ($args[0] == '-') {
                        $this->key = substr($args, 1);
                } elseif (preg_match("/(.*?)=(.*)/", $args, $match)) {
                        $this->key = $match[1];
                        $this->val = $match[2];
                } else {
                        $this->key = $args;
                }
        }

}

/**
 * Console application base class.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Application extends \Phalcon\CLI\Console
{

        protected $_namespace = 'OpenExam\Console\Tasks';

        /**
         * Process command line arguments.
         * 
         * Tasks works similar to controllers: Given the task, action and 
         * params argument, the target task/action is called using params 
         * as parameters. This function processes the argument list and calls 
         * parent handle() function. 
         * 
         * If arguments are passed, then they are feed to the handle()
         * method direct. The arguments array should have the by handle() 
         * method expected format.
         * 
         * If arguments are null, then the argv array is scanned. Both long
         * (--key[=val]) and short (-k) options and [task [action [params...]]] 
         * list is supported.
         * 
         * <code>
         * // Using long options:
         * php app/cli.php --database=migrate --table1 --table2
         *      -- or --
         * php app/cli.php --database --migrate --table1 --table2
         * 
         * Array
         * (
         *      [task] => task
         *      [action] => migrate
         *      [params] => Array
         *          (
         *              [0] => table1
         *              [1] => table2
         *          )
         * )
         * <code>
         * 
         * <code>
         * // Using handler options:
         * php app/cli.php database migrate table
         * Array
         * (
         *      [task] => database
         *      [action] => migrate
         *      [params] => Array
         *          (
         *              [0] => table
         *          )
         * )
         * <code>
         * 
         * @param array $arguments The command line arguments.
         */
        public function process($arguments = null)
        {
                if (!isset($arguments)) {
                        global $argv;
                        $arguments = array();

                        foreach ($argv as $args) {
                                $option = new CommandOption($args);
                                if (isset($option->val)) {
                                        $arguments[] = $option->key;
                                        $arguments[] = $option->val;
                                } else {
                                        $arguments[] = $option->key;
                                }
                        }

                        if (strstr($arguments[0], '.php')) {
                                array_shift($arguments);
                        }

                        $argv = $arguments;
                        $arguments = array();

                        foreach ($argv as $args) {
                                if (!isset($arguments['task'])) {
                                        $arguments['task'] = $args;
                                } elseif (!isset($arguments['action'])) {
                                        $arguments['action'] = $args;
                                } elseif (!isset($arguments['params'])) {
                                        $arguments['params'] = array($args);
                                } else {
                                        $arguments['params'][] = $args;
                                }
                        }
                }

                $this->handle($arguments);
        }

        public function handle($arguments = null)
        {
                $router = new \Phalcon\CLI\Router();
                $router->setDefaultTask('main');
                $router->setDefaultAction('index');
                $router->handle($arguments);

                $this->_dependencyInjector->set('router', $router);

                $route = array(
                        'task'   => $router->getTaskName(),
                        'action' => $router->getActionName(),
                        'params' => $router->getParams()
                );

                if (!isset($route['task'])) {
                        $route['task'] = 'main';
                }
                if (!isset($route['action'])) {
                        $route['action'] = 'index';
                }

                // 
                // Fix namespace issued with CLI router:
                // 
                $route['task'] = sprintf("%s\%s", $this->_namespace, \Phalcon\Text::camelize($route['task']));

                parent::handle($route);         // Route CLI application.
        }

}
