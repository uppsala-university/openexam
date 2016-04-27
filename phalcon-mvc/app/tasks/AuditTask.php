<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuditTask.php
// Created: 2016-04-27 05:17:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

/**
 * Audit trails task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class AuditTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $options;

        public function __construct()
        {
                if (!$this->getDI()->has('audit')) {
                        throw new Exception("The audit service is missing");
                }
                if (!$this->getDI()->has('dbaudit')) {
                        throw new Exception("The audit database connection is missing");
                }
        }

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Audit trails.',
                        'action'   => '--audit',
                        'usage'    => array(
                                '--show [--model=name]',
                                '--data [--model=name] [--id=num] [--user=str] [--type=action] [--time=str] [--fuzzy=str] [--decode]'
                        ),
                        'options'  => array(
                                '--show'        => 'Show current configuration.',
                                '--data'        => 'Query audit data.',
                                '--model=name'  => 'Select this model.',
                                '--id=num'      => 'Select this object ID.',
                                '--user=str'    => 'Select this username.',
                                '--type=action' => 'Select this action (create, update or delete).',
                                '--time=str'    => 'Select this date/time.',
                                '--fuzzy=str'   => 'Fuzzy search in changes field.',
                                '--decode'      => 'Decode changes data.',
                                '--verbose'     => 'Be more verbose.'),
                        'examples' => array(
                                array(
                                        'descr'   => 'List models with audit configuration',
                                        'command' => '--show'
                                ),
                                array(
                                        'descr'   => 'Show configuration details for answer model',
                                        'command' => '--show --verbose --model=answer'
                                ),
                                array(
                                        'descr'   => 'Query and decode all audit data for user',
                                        'command' => '--data --user=user1@example.com --verbose --decode'
                                ),
                                array(
                                        'descr'   => 'Find all answers for user on given date/time',
                                        'command' => '--data --user=user1@example.com --model=answer --time="2016-04-27 04:55"'
                                ),
                                array(
                                        'descr'   => 'Find all events for one particular answer',
                                        'command' => '--data --model=answer --id=138462'
                                ),
                                array(
                                        'descr'   => 'Make a fuzzy search in changes',
                                        'command' => '--data --model=answer --fuzzy="Some text"'
                                ))
                );
        }

        /**
         * Show config action.
         * @param array $params
         */
        public function dataAction($params = array())
        {
                $this->setOptions($params, 'data');

                if ($this->options['model']) {
                        $this->dataQuery($this->options['model']);
                } else {
                        foreach (self::getModels() as $model) {
                                $this->dataQuery($model);
                        }
                }
        }

        /**
         * Query audit data.
         * @param string $model The resource name.
         */
        private function dataQuery($model)
        {
                $params = array();

                if ($this->options['id']) {
                        $params[] = sprintf("rid = %d", $this->options['id']);
                }
                if ($this->options['type']) {
                        $params[] = sprintf("type = '%s'", $this->options['type']);
                }
                if ($this->options['user']) {
                        $params[] = sprintf("user = '%s'", $this->options['user']);
                }
                if ($this->options['time']) {
                        $params[] = sprintf("time LIKE '%%%s%%'", $this->options['time']);
                }
                if ($this->options['fuzzy']) {
                        $params[] = sprintf("changes LIKE '%%%s%%'", $this->options['fuzzy']);
                }


                if (count($params)) {
                        $sql = sprintf("SELECT * FROM %s WHERE %s", $model, implode(" AND ", $params));
                } else {
                        $sql = sprintf("SELECT * FROM %s", $model);
                }

                $dbh = $this->dbaudit;
                $sth = $dbh->prepare($sql);
                $res = $sth->execute();

                if (!$res) {
                        $this->flash->error(print_f($sth->errorInfo()));
                        return false;
                }

                $this->flash->notice($model);
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                        if ($this->options['decode']) {
                                $row['changes'] = unserialize($row['changes']);
                        }
                        if ($this->options['verbose']) {
                                $this->flash->success(print_r($row, true));
                        } else {
                                $this->flash->success(print_r($row['changes'], true));
                        }
                }
        }

        /**
         * Show config action.
         * @param array $params
         */
        public function showAction($params = array())
        {
                $this->setOptions($params, 'show');

                if ($this->options['model']) {
                        $this->showModel($this->options['model']);
                } else {
                        foreach (self::getModels() as $model) {
                                $this->showModel($model);
                        }
                }
        }

        /**
         * Show this model.
         * @param string $model The resource name.
         */
        private function showModel($model)
        {
                if ($this->audit->hasConfig($model)) {
                        $this->flash->notice($model);
                        if ($this->options['verbose']) {
                                $config = $this->audit->getConfig($model);
                                $this->flash->notice(sprintf("  actions: %s", implode(", ", $config->getActions())));
                                $this->flash->notice(sprintf("  targets: %s", implode(", ", $config->getTargets())));
                                foreach ($config->getTargets() as $target) {
                                        $this->flash->notice(
                                            sprintf("  %s -> %s", $target, json_encode($config->getTarget($target))
                                        ));
                                }
                        }
                } elseif ($this->options['verbose']) {
                        $this->flash->warning($model);
                }
        }

        private static function getModels()
        {
                return array(
                        'access',
                        'admin',
                        'answer',
                        'computer',
                        'contributor',
                        'corrector',
                        'decoder',
                        'exam',
                        'file',
                        'invigilator',
                        'lock',
                        'question',
                        'resource',
                        'result',
                        'room',
                        'session',
                        'setting',
                        'student',
                        'teacher',
                        'topic'
                );
        }

        /**
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->options = array('verbose' => false, 'decode' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'show', 'data', 'model', 'user', 'type', 'time', 'id', 'fuzzy', 'decode');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        if (!isset($this->options[$option])) {
                                $this->options[$option] = false;
                        }
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }
        }

}
