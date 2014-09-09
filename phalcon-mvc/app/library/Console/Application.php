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

        /**
         * Process command line arguments.
         * 
         * Tasks works similar to controllers: Given the task, action and params
         * argument, the target task/action is called using params as parameters. 
         * 
         * This function process $argument parameter and command line arguments 
         * (from PHP builtin $argv). The merged array is used when calling the
         * parent handle() method.
         * 
         * Both long (--key[=val]) and short (-k) command line options are 
         * supported. Command line options can also be [task [action [params...]]] 
         * 
         * Example 1:
         * <code>
         * // Script database.php:
         * $console = new Application($di);
         * $console->process(array('task' => 'database'));
         * 
         * // Run from command line:
         * php script/database.php --migrate exams students
         * 
         * // The task definition after process():
         * Array
         * (
         *      [task] => database
         *      [action] => migrate
         *      [params] => Array
         *          (
         *              [0] => exams
         *              [1] => students
         *          )
         * )
         * <code>
         * 
         * Example 2 (same task called):
         * <code>
         * // Script cli.php:
         * $console = new Application($di);
         * $console->process();
         * 
         * // Run from command line:
         * php script/cli.php --database --migrate exams students
         * 
         * // The task definition after process():
         * Array
         * (
         *      [task] => database
         *      [action] => migrate
         *      [params] => Array
         *          (
         *              [0] => exams
         *              [1] => students
         *          )
         * )
         * <code>
         * 
         * @param array $arguments The task specification.
         */
        public function process($arguments = null)
        {
                global $argv;

                $options = array();

                print_r($arguments);
                print_r($argv);

                foreach ($argv as $args) {
                        $option = new CommandOption($args);
                        if (isset($option->val)) {
                                $options[] = $option->key;
                                $options[] = $option->val;
                        } else {
                                $options[] = $option->key;
                        }
                }

                if (strstr($options[0], '.php')) {
                        array_shift($options);
                }
                
                if(!isset($arguments)) {
                        $arguments = array();
                }

                foreach ($options as $args) {
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
                
                parent::handle($arguments);
        }

}
