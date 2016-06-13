<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DiagnosticsTask.php
// Created: 2016-05-22 23:18:04
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Monitor\Config as MonitorConfig;
use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Collector\CollectorFactory;
use OpenExam\Library\Monitor\Performance\Counter;
use SimpleXMLElement;

abstract class ExportFormatter
{

        /**
         * The performance counter.
         * @var Counter 
         */
        protected $_counter;

        /**
         * Contructor.
         * @param Counter $counter The performance counter.
         */
        protected function __construct($counter)
        {
                $this->_counter = $counter;
        }

        /**
         * Create export formatter.
         * 
         * @param string $format The export format.
         * @param Counter $counter The performance counter.
         * @return ExportFormatter
         * @throws Exception
         */
        public static function create($format, $counter)
        {
                switch ($format) {
                        case 'xml':
                                return new ExportFormatterXML($counter);
                        case 'php':
                                return new ExportFormatterPHP($counter);
                        case 'csv':
                                return new ExportFormatterCSV($counter);
                        case 'tab':
                                return new ExportFormatterTAB($counter);
                        default:
                                throw new Exception("Unknown export format $format");
                }
        }

        /**
         * Export data to standard output.
         */
        abstract public function export();
}

/**
 * Export data in XML format.
 */
class ExportFormatterXML extends ExportFormatter
{

        public function export()
        {
                $xmlstr = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?><counters type=\"%s\"></counters>", $this->_counter->getType());
                $xmldoc = new SimpleXMLElement($xmlstr);

                foreach ($this->_counter->getData() as $data) {
                        $counter = $xmldoc->addChild("counter");
                        foreach ($data as $ckey => $cval) {
                                if (is_array($cval)) {
                                        $dnode = $counter->addChild("data");
                                        foreach ($cval as $dkey => $dval) {
                                                $cnode = $dnode->addChild($dkey);
                                                foreach ($dval as $skey => $sval) {
                                                        $cnode->addChild($skey, $sval);
                                                }
                                        }
                                } else {
                                        $counter->addAttribute($ckey, $cval);
                                }
                        }
                }

                echo $xmldoc->asXML();
        }

}

/**
 * Export data in TAB-separated format.
 * 
 * The data is hierachal, but we can't represent that in TAB-separated 
 * foramt. The solution is to use indention instead.
 */
class ExportFormatterTAB extends ExportFormatter
{

        private static function printArray($key, $arr, $indent = 0)
        {
                printf("%s%s\n", str_repeat("\t", $indent++), $key);
                foreach ($arr as $key => $val) {
                        if (is_array($val)) {
                                self::printArray($key, $val, $indent);
                        } else {
                                printf("%s%s\t%s\n", str_repeat("\t", $indent), $key, $val);
                        }
                }
        }

        public function export()
        {
                foreach ($this->_counter->getData() as $data) {
                        self::printArray("counter", $data, 0);
                }
        }

}

/**
 * Export data in CSV format.
 */
class ExportFormatterCSV extends ExportFormatter
{

        private static function printArray($key, $arr, $indent = 0)
        {
                printf("%s\"%s\"\n", str_repeat("\"\",", $indent++), $key);
                foreach ($arr as $key => $val) {
                        if (is_array($val)) {
                                self::printArray($key, $val, $indent);
                        } else {
                                printf("%s\"%s\",\"%s\"\n", str_repeat("\"\",", $indent), $key, $val);
                        }
                }
        }

        public function export()
        {
                foreach ($this->_counter->getData() as $data) {
                        self::printArray("counter", $data, 0);
                }
        }

}

/**
 * Export data in PHP format.
 */
class ExportFormatterPHP extends ExportFormatter
{

        public function export()
        {
                echo var_export($this->_counter->getData(), true);
        }

}

/**
 * System performance task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class PerformanceTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'System performance tool',
                        'action'   => '--performance',
                        'usage'    => array(
                                '--collect --counter=name [--rate=sec] [--source=name] [--uid={str|num}]',
                                '--query   --counter=name [--time=str] [--host=str] [--addr=str] [--milestone=str] [--source=name] [--limit=num] [--export=fmt]',
                                '--clean  [--counter=name] [--days=num|--hours=num|--time=str] [--host=str] [--addr=str] [--source=name]',
                                '--show [--counter=name]'
                        ),
                        'options'  => array(
                                '--collect'       => 'Collect performance statistics.',
                                '--query'         => 'Check performance counters.',
                                '--clean'         => 'Cleanup performance statistics.',
                                '--show'          => 'Display current monitor configuration.',
                                '--uid={str|num}' => 'Run task as this used or UID.',
                                '--counter=name'  => 'The counter name.',
                                '--rate=sec'      => 'The sample rate (colleting).',
                                '--source=name'   => 'Match on source (use ":" to match multiple).',
                                '--time=str'      => 'Match on datetime.',
                                '--host=str'      => 'Match on hostname (FQHN).',
                                '--addr=str'      => 'Match on IP-address.',
                                '--milestone=str' => "Match milestorn ('minute','hour','day','week','month','year')'",
                                '--limit=num'     => 'Limit number of returned records.',
                                '--export[=fmt]'  => 'Export data for external analyze.',
                                '--days=num'      => 'Expire records older than num days (clean).',
                                '--hours=num'     => 'Expire records older than num hours (clean).',
                                '--verbose'       => 'Be more verbose.'
                        ),
                        'aliases'  => array(
                                '--server' => 'Same as --counter=server (server performance).',
                                '--system' => 'Same as --counter=system (system performance).',
                                '--net'    => 'Same as --counter=net (network performance).',
                                '--apache' => 'Same as --counter=apache (web server performance).',
                                '--mysql'  => 'Same as --counter=mysql (database performance).',
                                '--disk'   => 'Same as --counter=disk (hard drive performance).',
                                '--part'   => 'Same as --counter=part (partition performance).',
                                '--fs'     => 'Same as --counter=fs (file system performance).',
                                '--php'    => 'Same as --export=php (export as PHP array).',
                                '--csv'    => 'Same as --export=csv (export in CSV-format).',
                                '--tab'    => 'Same as --export=tab (export in TAB-format).',
                                '--xml'    => 'Same as --export=xml (export in XML-format).'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Collect disk performance statistics, drop root privileges (if any)',
                                        'command' => '--collect --disk --source==sda --rate=5 --uid=daemon'
                                ),
                                array(
                                        'descr'   => 'Collect system performance statistics',
                                        'command' => '--collect --server --rate=2'
                                ),
                                array(
                                        'descr'   => 'Collect file system performance (usage) on home directories',
                                        'command' => '--collect --fs --source=/home'
                                ),
                                array(
                                        'descr'   => 'Collect file system performance (usage) on all mount points using default sample rate',
                                        'command' => '--collect --fs'
                                ),
                                array(
                                        'descr'   => 'Collect network performance for multiple interfaces',
                                        'command' => '--collect --net --rate=2 --source=eth0:eth1'
                                ),
                                array(
                                        'descr'   => 'Custom params can be passed to collector (--user, --disk, --path, --type, --name or --part)',
                                        'command' => '--collect --fs --path=/var/data:/home --type=ext4:btrfs'
                                ),
                                array(
                                        'descr'   => 'Display all available performance counters',
                                        'command' => '--query'
                                ),
                                array(
                                        'descr'   => 'Get last 100 Apache performance counters in XML format',
                                        'command' => '--query --counter=apache --limit=100 --export=xml'
                                ),
                                array(
                                        'descr'   => 'Cleanup collected MySQL statistics older than 5 days',
                                        'command' => '--clean --mysql --days=5'
                                ),
                                array(
                                        'descr'   => 'Cleanup file system statistics for /data collected from server.example.com',
                                        'command' => '--clean --fs --source=/data --host=server.example.com'
                                )
                        )
                );
        }

        /**
         * Performace collection action.
         * @param array $params
         */
        public function collectAction($params = array())
        {
                $this->setOptions($params, 'collect');

                $config = new MonitorConfig();
                $params = $config->hasConfig($this->_options['counter']);

                if (!$params) {
                        $this->flash->error(
                            sprintf("The requested performance counter '%s' is disabled", $this->_options['counter'])
                        );
                        return false;
                }

                $params = $config->getConfig($this->_options['counter']);

                // 
                // Inject standard options:
                // 
                if ($this->_options['rate']) {
                        $params['params']['rate'] = $this->_options['rate'];
                }
                if ($this->_options['source']) {
                        $params['params']['source'] = $this->_options['source'];
                }

                // 
                // Inject custom options:
                // 
                if ($this->_options['user']) {
                        $params['params']['user'] = $this->_options['user'];
                }
                if ($this->_options['disk']) {
                        $params['params']['disk'] = $this->_options['disk'];
                }
                if ($this->_options['path']) {
                        $params['params']['path'] = $this->_options['path'];
                }
                if ($this->_options['type']) {
                        $params['params']['type'] = $this->_options['type'];
                }
                if ($this->_options['name']) {
                        $params['params']['name'] = $this->_options['name'];
                }
                if ($this->_options['part']) {
                        $params['params']['part'] = $this->_options['part'];
                }

                // 
                // Drop privileges if requested:
                // 
                if ($this->_options['uid']) {
                        if (!extension_loaded('posix')) {
                                $this->flash->warning("The posix extension is not loaded. Can't drop user privileges.");
                        } else {
                                $this->setProcessOwner($this->_options['uid']);
                        }
                }

                $performance = CollectorFactory::create($this->_options['counter'], $params);
                $performance->start();
        }

        /**
         * Performace counter query action.
         * @param array $params
         */
        public function queryAction($params = array())
        {
                $this->setOptions($params, 'query');

                if ($this->_options['counter']) {
                        $performance = new Performance($this->_options['limit'], $this->_options);
                        $counter = $performance->getCounter($this->_options['counter']);

                        if ($this->_options['export']) {
                                $formatter = ExportFormatter::create($this->_options['export'], $counter);
                                $formatter->export();
                        } else {
                                $this->flash->success(sprintf("%s [%s] (%s)", $counter->getName(), $counter->getType(), $counter->getTitle()));
                                $this->flash->success(print_r($counter->getData(), true));
                        }
                } else {
                        $performance = new Performance();
                        $counters = $performance->getCounters();

                        $this->flash->success("*** Availible performance counters: ***");
                        $this->flash->success("");

                        foreach ($counters as $counter) {
                                $this->flash->success(sprintf("%s [%s] (%s)", $counter->getName(), $counter->getType(), $counter->getTitle()));
                                $this->flash->success(sprintf("\t%s", $counter->getDescription()));
                        }
                }
        }

        /**
         * Performace counter cleanup action.
         * @param array $params
         */
        public function cleanAction($params = array())
        {
                $this->setOptions($params, 'clean');

                if ($this->_options['milestone']) {
                        $this->flash->error("Cowardly refusing to clean milestones. Please handle that manual from the command line.");
                        return false;
                }
                if (!$this->_options['time']) {
                        $this->flash->error("The mandatory time argument is missing. May I suggest using the --days=num option?");
                        return false;
                }

                $params = array();      // Cleanup

                if ($this->_options['counter']) {
                        $params[] = sprintf("mode = '%s'", $this->_options['counter']);
                }
                if ($this->_options['source']) {
                        $params[] = sprintf("source = '%s'", $this->_options['source']);
                }
                if ($this->_options['host']) {
                        $params[] = sprintf("host = '%s'", $this->_options['host']);
                }
                if ($this->_options['addr']) {
                        $params[] = sprintf("addr = '%s'", $this->_options['addr']);
                }
                if ($this->_options['time']) {
                        $params[] = sprintf("time < '%s'", $this->_options['time']);
                }

                $sql = sprintf("DELETE FROM performance WHERE %s", implode(" AND ", $params));
                $dbh = $this->getDI()->get('dbwrite');
                $sth = $dbh->prepare($sql);
                $res = $sth->execute();

                if (!$res) {
                        $this->flash->error(print_r($sth->errorInfo()));
                        return false;
                } elseif ($this->_options['verbose'] && $sth->rowCount() > 0) {
                        $this->flash->success(sprintf("Cleaned up performance data older than %s (%d rows deleted)", $this->_options['time'], $sth->rowCount()));
                } elseif ($this->_options['verbose']) {
                        $this->flash->notice(sprintf("No performance data older than older than %s found (%d rows deleted)", $this->_options['time'], $sth->rowCount()));
                }
        }

        /**
         * Show performance counter config action.
         * @param array $params
         */
        public function showAction($params = array())
        {
                $this->setOptions($params, 'show');

                $config = new MonitorConfig();
                if (!$config->hasCounters()) {
                        $this->flash->error("Performance counters are disabled");
                        return;
                } elseif ($this->_options['verbose']) {
                        $this->flash->success("Monitor configuration:");
                        $this->flash->success(print_r($config->getConfig($this->_options['counter']), true));
                } else {
                        $this->flash->success(sprintf("Monitors enabled: %s", implode(", ", $config->getCounters())));
                }
        }

        /**
         * List performance counter config action.
         * @param array $params
         */
        public function listAction($params = array())
        {
                $this->setOptions($params, 'list');

                $config = new MonitorConfig();
                if ($config->hasCounters()) {
                        printf("%s\n", implode(" ", $config->getCounters()));
                }
        }

        /**
         * Set UID of current process.
         * 
         * @params string|int $uid The requested user.
         * @return boolean True if user privileges were dropped to requested uid.
         */
        private function setProcessOwner($uid)
        {
                if (is_string($uid)) {
                        if (!($pwd = posix_getpwnam($uid))) {
                                $this->flash->warning("Failed get passwd info for $uid");
                                return false;
                        } else {
                                $uid = $pwd['uid'];
                        }
                }

                if ($uid == posix_getuid()) {
                        return true;
                } elseif (!(posix_setuid($uid))) {
                        $this->flash->warning("Failed set UID to $uid");
                } else {
                        return true;
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
                $this->_options = array('verbose' => false, 'limit' => 20);

                // 
                // Supported options.
                // 
                $options = array(
                        'verbose', 'collect', 'query', 'clean', 'counter', 'show', 'list', 'uid',
                        'time', 'host', 'addr', 'milestone', 'source',
                        'rate', 'limit', 'export', 'days', 'hours',
                        'user', 'disk', 'path', 'type', 'name', 'part',
                        'disk', 'part', 'fs', 'server', 'system', 'net', 'apache', 'mysql', 'test',
                        'php', 'xml', 'csv', 'tab'
                );
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

                // 
                // Fix up aliases:
                // 
                if ($this->_options['disk']) {
                        $this->_options['counter'] = 'disk';
                }
                if ($this->_options['part']) {
                        $this->_options['counter'] = 'part';
                }
                if ($this->_options['fs']) {
                        $this->_options['counter'] = 'fs';
                }
                if ($this->_options['server']) {
                        $this->_options['counter'] = 'server';
                }
                if ($this->_options['system']) {
                        $this->_options['counter'] = 'system';
                }
                if ($this->_options['net']) {
                        $this->_options['counter'] = 'net';
                }
                if ($this->_options['apache']) {
                        $this->_options['counter'] = 'apache';
                }
                if ($this->_options['mysql']) {
                        $this->_options['counter'] = 'mysql';
                }
                if ($this->_options['test']) {
                        $this->_options['counter'] = 'test';
                }

                if ($this->_options['php']) {
                        $this->_options['export'] = 'php';
                }
                if ($this->_options['xml']) {
                        $this->_options['export'] = 'xml';
                }
                if ($this->_options['csv']) {
                        $this->_options['export'] = 'csv';
                }
                if ($this->_options['tab']) {
                        $this->_options['export'] = 'tab';
                }

                // 
                // The source parameter is either string or array.
                // 
                if (strstr($this->_options['source'], ':')) {
                        $this->_options['source'] = explode(":", $this->_options['source']);
                }

                // 
                // Calculate time stamp for number of hours or days (clean).
                // 
                if ($this->_options['hours']) {
                        $this->_options['time'] = strftime(
                            "%Y-%m-%d %H:%M:%S", time() - 3600 * intval($this->_options['hours'])
                        );
                }
                if ($this->_options['days']) {
                        $this->_options['time'] = strftime(
                            "%Y-%m-%d %H:%M:%S", time() - 24 * 3600 * intval($this->_options['days'])
                        );
                }
        }

}
