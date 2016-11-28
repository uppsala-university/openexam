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
        private $_options;

        public function initialize()
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
                                '--show  [--model=name]',
                                '--data  [--model=name] [--time=str] [--id=num] [--user=str] [--type=action] [--fuzzy=str] [--decode]',
                                '--clean [--model=name] [--time=str] [--days=num]',
                                '--stat'
                        ),
                        'options'  => array(
                                '--show'        => 'Show current configuration.',
                                '--data'        => 'Query audit data.',
                                '--clean'       => 'Cleanup expired audit data',
                                '--stat'        => 'Display audit statistics',
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
                                ),
                                array(
                                        'descr'   => 'Cleanup audit data older that 90 days',
                                        'command' => '--cleanup --days=90'
                                ),
                                array(
                                        'descr'   => 'Display audit statistics',
                                        'command' => '--stat'
                                )
                        )
                );
        }

        /**
         * Cleanup expired audit data action.
         * @param array $params
         */
        public function cleanAction($params = array())
        {
                $this->setOptions($params, 'clean');

                if ($this->_options['model']) {
                        $this->dataClean($this->_options['model']);
                } else {
                        foreach (self::getModels() as $model) {
                                $this->dataClean($model);
                        }
                }
        }

        /**
         * Show config action.
         * @param array $params
         */
        public function dataAction($params = array())
        {
                $this->setOptions($params, 'data');

                if ($this->_options['model']) {
                        $this->dataQuery($this->_options['model']);
                } else {
                        foreach (self::getModels() as $model) {
                                $this->dataQuery($model);
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

                if ($this->_options['model']) {
                        $this->showModel($this->_options['model']);
                } else {
                        foreach (self::getModels() as $model) {
                                $this->showModel($model);
                        }
                }
        }

        /**
         * Show audit statistics.
         * @param array $params
         */
        public function statAction($params = array())
        {
                $this->setOptions($params, 'stat');

                foreach (self::getModels() as $model) {
                        $this->statModel($model);
                }
        }

        /**
         * Query audit data.
         * @param string $model The resource name.
         */
        private function dataQuery($model)
        {
                $params = array(sprintf("res = '%s'", $model));

                $config = $this->audit->getConfig($model);
                $target = $config->getTarget('data');

                if (!$target) {
                        $this->flash->warning("Skipping model $model (data config is missing)");
                        return false;
                }

                if ($this->_options['id']) {
                        $params[] = sprintf("rid = %d", $this->_options['id']);
                }
                if ($this->_options['type']) {
                        $params[] = sprintf("type = '%s'", $this->_options['type']);
                }
                if ($this->_options['user']) {
                        $params[] = sprintf("user = '%s'", $this->_options['user']);
                }
                if ($this->_options['time']) {
                        $params[] = sprintf("time LIKE '%%%s%%'", $this->_options['time']);
                }
                if ($this->_options['fuzzy']) {
                        $params[] = sprintf("changes LIKE '%%%s%%'", $this->_options['fuzzy']);
                }

                $dbh = $this->getDI()->get($target['connection']);
                $sql = sprintf("SELECT * FROM %s WHERE %s", $dbh->escapeIdentifier($target['table']), implode(" AND ", $params));

                $sth = $dbh->prepare($sql);
                $res = $sth->execute();

                if (!$res) {
                        $this->flash->error(print_f($sth->errorInfo()));
                        return false;
                }

                $this->flash->notice($model);
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                        if ($this->_options['decode']) {
                                $row['changes'] = unserialize($row['changes']);
                        }
                        if ($this->_options['verbose']) {
                                $this->flash->success(print_r($row, true));
                        } else {
                                $this->flash->success(print_r($row['changes'], true));
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
                        if ($this->_options['verbose']) {
                                $config = $this->audit->getConfig($model);
                                $this->flash->notice(sprintf("  actions: %s", implode(", ", $config->getActions())));
                                $this->flash->notice(sprintf("  targets: %s", implode(", ", $config->getTargets())));
                                foreach ($config->getTargets() as $target) {
                                        $this->flash->notice(
                                            sprintf("  %s -> %s", $target, json_encode($config->getTarget($target))
                                        ));
                                }
                        }
                } elseif ($this->_options['verbose']) {
                        $this->flash->warning($model);
                }
        }

        /**
         * Statistics for this model.
         * @param string $model The resource name.
         */
        private function statModel($model)
        {
                $params = array(sprintf("res = '%s'", $model));

                $config = $this->audit->getConfig($model);
                $target = $config->getTarget('data');

                if (!$target) {
                        $this->flash->warning("Skipping model $model (data config is missing)");
                        return false;
                }

                $dbh = $this->getDI()->get($target['connection']);
                $sql = sprintf("SELECT SUM(LENGTH(changes)) AS len, COUNT(*) AS num FROM %s WHERE %s", $dbh->escapeIdentifier($target['table']), implode(" AND ", $params));

                $res = $dbh->query($sql);
                $obj = $res->fetch(\Phalcon\Db::FETCH_OBJ);

                self::setUnit($obj);

                $this->flash->notice(sprintf("%s: ->", $model));
                $this->flash->notice(sprintf("  entries:\t%d\t(records)", $obj->num));
                $this->flash->notice(sprintf("  size:\t%.02f\t(%s)", $obj->len, $obj->val));
        }

        private function dataClean($model)
        {
                $params = array(sprintf("res = '%s'", $model));

                $config = $this->audit->getConfig($model);
                $target = $config->getTarget('data');

                if (!$target) {
                        $this->flash->warning("Skipping model $model (data config is missing)");
                        return false;
                }

                if ($this->_options['id']) {
                        $params[] = sprintf("rid = %d", $this->_options['id']);
                }
                if ($this->_options['type']) {
                        $params[] = sprintf("type = '%s'", $this->_options['type']);
                }
                if ($this->_options['user']) {
                        $params[] = sprintf("user = '%s'", $this->_options['user']);
                }
                if ($this->_options['time']) {
                        $params[] = sprintf("time < '%s'", $this->_options['time']);
                }
                if ($this->_options['fuzzy']) {
                        $params[] = sprintf("changes LIKE '%%%s%%'", $this->_options['fuzzy']);
                }

                $dbh = $this->getDI()->get($target['connection']);
                $sql = sprintf("DELETE FROM %s WHERE %s", $dbh->escapeIdentifier($target['table']), implode(" AND ", $params));

                $sth = $dbh->prepare($sql);
                $res = $sth->execute();

                if (!$res) {
                        $this->flash->error(print_f($sth->errorInfo()));
                        return false;
                } elseif ($this->_options['verbose'] && $sth->rowCount() > 0) {
                        $this->flash->success(sprintf("Expired audit data older than %s in %s (%d rows deleted)", $this->_options['time'], $model, $sth->rowCount()));
                } elseif ($this->_options['verbose']) {
                        $this->flash->notice(sprintf("No audit data older than %s found in %s (%d rows deleted)", $this->_options['time'], $model, $sth->rowCount()));
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

        private static function setUnit(&$obj)
        {
                if (!isset($obj->len)) {
                        return;
                }
                
                if ($obj->len < 1024) {
                        $obj->val = 'B';
                }
                if ($obj->len > 1024) {
                        $obj->val = 'kB';
                        $obj->len /= (float) 1024;
                }
                if ($obj->len > 1024) {
                        $obj->val = 'MB';
                        $obj->len /= (float) 1024;
                }
                if ($obj->len > 1024) {
                        $obj->val = 'GB';
                        $obj->len /= (float) 1024;
                }
                if ($obj->len > 1024) {
                        $obj->val = 'TB';
                        $obj->len /= (float) 1024;
                }
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
                $this->_options = array('verbose' => false, 'decode' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'show', 'data', 'cleanup', 'model', 'user', 'type', 'time', 'id', 'fuzzy', 'decode', 'days');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        if (!isset($this->_options[$option])) {
                                $this->_options[$option] = false;
                        }
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->_options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->_options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->_options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }

                if ($this->_options['days']) {
                        $this->_options['time'] = strftime(
                            "%Y-%m-%d %H:%M:%S", time() - 24 * 3600 * intval($this->_options['days'])
                        );
                }
        }

}
