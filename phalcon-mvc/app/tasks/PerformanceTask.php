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

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Collector\Apache as ApachePerformanceCollector;
use OpenExam\Library\Monitor\Performance\Collector\Disk as DiskPerformanceCollector;
use OpenExam\Library\Monitor\Performance\Collector\MySQL as MySQLPerformanceCollector;
use OpenExam\Library\Monitor\Performance\Collector\Partition as PartitionPerformanceCollector;
use OpenExam\Library\Monitor\Performance\Collector\Server as ServerPerformanceCollector;
use OpenExam\Library\Monitor\Performance\Counter;

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
                $xmldoc = new \SimpleXMLElement($xmlstr);

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
                                '--collect --counter=name|--disk=name|--part=name [--rate=sec] [--user=str]',
                                '--query   --counter=name|--disk=name|--part=name [--time=str] [--host=str] [--addr=str] [--milestone=str] [--source=str] [--limit=num] [--export=fmt]'
                        ),
                        'options'  => array(
                                '--collect'       => 'Collect performance statistics.',
                                '--query'         => 'Check performance counters.',
                                '--counter=name'  => 'The counter name.',
                                '--disk=name'     => 'Disk performace counter.',
                                '--part=name'     => 'Partition performance counter.',
                                '--rate=sec'      => 'The sample rate (colleting).',
                                '--user=str'      => 'The service process user (e.g. apache).',
                                '--time=str'      => 'Match on datetime.',
                                '--host=str'      => 'Match on hostname (FQHN).',
                                '--addr=str'      => 'Match on IP-address.',
                                '--milestone=str' => "Match milestorn ('minute','hour','day','week','month','year')'",
                                '--source=str'    => 'Match on source.',
                                '--limit=num'     => 'Limit number of returned records.',
                                '--export[=fmt]'  => 'Export data for external analyze.',
                                '--server'        => 'Alias for --counter=server.',
                                '--system'        => 'Alias for --counter=system.',
                                '--net'           => 'Alias for --counter=net.',
                                '--apache'        => 'Alias for --counter=apache.',
                                '--mysql'         => 'Alias for --counter=mysql.',
                                '--php'           => 'Alias for --export=php (export as PHP array).',
                                '--csv'           => 'Alias for --export=csv (export in CSV-format).',
                                '--tab'           => 'Alias for --export=tab (export in TAB-format).',
                                '--xml'           => 'Alias for --export=xml (export in XML-format).',
                                '--verbose'       => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Collect disk performance statistics',
                                        'command' => '--collect --disk=sda --rate=5'
                                ),
                                array(
                                        'descr'   => 'Collect system performance statistics',
                                        'command' => '--collect --server --rate=2'
                                ),
                                array(
                                        'descr'   => 'Collect Apache performance using default sample rate',
                                        'command' => '--collect --apache'
                                ),
                                array(
                                        'descr'   => 'Show all performance counters',
                                        'command' => '--query'
                                ),
                                array(
                                        'descr'   => 'Get last 100 Apache performance counters in XML format',
                                        'command' => '--query --counter=apache --limit=100 --export=xml'
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

                if ($this->_options['apache']) {
                        $performance = new ApachePerformanceCollector($this->_options['user'], $this->_options['rate']);
                        $performance->start();
                } elseif ($this->_options['mysql']) {
                        $performance = new MySQLPerformanceCollector($this->_options['rate']);
                        $performance->start();
                } elseif ($this->_options['server']) {
                        $performance = new ServerPerformanceCollector($this->_options['rate']);
                        $performance->start();
                } elseif ($this->_options['disk']) {
                        $performance = new DiskPerformanceCollector($this->_options['disk'], $this->_options['rate']);
                        $performance->start();
                } elseif ($this->_options['part']) {
                        $performance = new PartitionPerformanceCollector($this->_options['part'], $this->_options['rate']);
                        $performance->start();
                } else {
                        $this->flash->error("Requested counter was not found, see --help");
                }
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
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->_options = array('verbose' => false, 'rate' => 10, 'user' => 'apache', 'limit' => 20);

                // 
                // Supported options.
                // 
                $options = array(
                        'verbose', 'collect', 'query', 'counter',
                        'disk', 'part', 'time', 'host', 'addr', 'milestone', 'source',
                        'rate', 'user', 'limit', 'export',
                        'server', 'system', 'net', 'apache', 'mysql',
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
        }

}
