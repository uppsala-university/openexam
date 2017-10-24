<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ArchiveTask.php
// Created: 2017-09-19 19:03:34
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use FilesystemIterator;
use OpenExam\Library\Core\Exam\Archive;
use OpenExam\Models\Exam;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultSet;

/**
 * Use this task to archive exams.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ArchiveTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;
        /**
         * The result directory.
         * @var string 
         */
        private $_destdir;

        public function initialize()
        {
                $this->_destdir = sprintf("%s/archive", $this->config->application->cacheDir);
        }

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Management tool for archiving exams.',
                        'action'   => '--archive',
                        'usage'    => array(
                                '--create [--exam=num] [--start=date] [--end=date] [--all] [--force]',
                                '--delete [--exam=num] [--start=date] [--end=date] [--all] [--force]',
                                '--update [--exam=num] [--start=date] [--end=date] [--all] [--force]',
                                '--list|--missing|--verify',
                        ),
                        'options'  => array(
                                '--create'     => 'Create exam archive files.',
                                '--delete'     => 'Delete exam archive files.',
                                '--update'     => 'Update exam archive files (if needed).',
                                '--list'       => 'List exam archives.',
                                '--missing'    => 'Find exams not archived.',
                                '--verify'     => 'Verify exam archives.',
                                '--exam=num'   => 'Use this particular exam.',
                                '--start=date' => 'Process exams newer than date.',
                                '--end=date'   => 'Process exams older than date.',
                                '--all'        => 'Process all exams.',
                                '--force'      => 'Force generate archives.',
                                '--verbose'    => 'Be more verbose.',
                                '--quiet'      => 'Suppress warnings please.',
                                '--dry-run'    => 'Just print whats going to be done.'
                        ),
                        'aliases'  => array(
                                '--silent' => 'Same as --quiet --verbose=false'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'List all archived exams',
                                        'command' => '--list'
                                ),
                                array(
                                        'descr'   => 'Create archives for all exams',
                                        'command' => '--create --all'
                                ),
                                array(
                                        'descr'   => 'Delete archives for all exams',
                                        'command' => '--delete --all'
                                ),
                                array(
                                        'descr'   => 'Find exams not being archived',
                                        'command' => '--missing'
                                ),
                                array(
                                        'descr'   => 'Create archives for year 2017 overwriting existing',
                                        'command' => '--create --start=2017-01-01 --end=2017-12-31 --force'
                                ),
                                array(
                                        'descr'   => 'Create archive for single exam',
                                        'command' => '--create --exam=73624'
                                ),
                                array(
                                        'descr'   => 'Update archive if associated exam has been modified',
                                        'command' => '--update'
                                ))
                );
        }

        /**
         * Create exam archives action.
         * @param array $params
         */
        public function createAction($params = array())
        {
                $this->setOptions($params, 'create');

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $archive = new Archive($exam);
                        if ($archive->exists() && $this->_options['quiet'] == false) {
                                $this->flash->warning(sprintf("Skipping create for exam %d because archive exist (see --force)", $exam->id));
                        }
                        if ($archive->exists() && $this->_options['force'] == false) {
                                continue;
                        }
                        if ($this->_options['verbose']) {
                                $this->flash->notice(sprintf("Creating archive for exam %d", $exam->id));
                        }
                        if ($this->_options['dry-run'] == false) {
                                $archive->create();
                        }
                }
        }

        /**
         * Delete exam archives action.
         * @param array $params
         */
        public function deleteAction($params = array())
        {
                $this->setOptions($params, 'delete');

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $archive = new Archive($exam);
                        if ($archive->exists() == false) {
                                continue;
                        }
                        if ($this->_options['verbose']) {
                                $this->flash->notice(sprintf("Deleting archive for exam %d", $exam->id));
                        }
                        if ($this->_options['dry-run'] == false) {
                                $archive->delete();
                        }
                }
        }

        /**
         * Update exam archives action.
         * @param array $params
         */
        public function updateAction($params = array())
        {
                $this->setOptions($params, 'update');

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $archive = new Archive($exam);
                        if ($archive->exists() == false) {
                                continue;
                        }
                        if (filemtime($archive->getFilename()) > strtotime($exam->updated)) {
                                continue;
                        }
                        if ($this->_options['verbose']) {
                                $this->flash->notice(sprintf("Updating archive for exam %d", $exam->id));
                        }
                        if ($this->_options['dry-run'] == false) {
                                $archive->create();
                        }
                }
        }

        /**
         * List exam archives action.
         * @param array $params
         */
        public function listAction($params = array())
        {
                $this->setOptions($params, 'list');

                $iterator = new FilesystemIterator($this->_destdir);
                foreach ($iterator as $file) {
                        if ($file->isFile()) {
                                $this->flash->success(sprintf("%s\t%d", $file->getBasename(), $file->getSize()));
                        }
                }
        }

        /**
         * Find exams without archives action.
         * @param array $params
         */
        public function missingAction($params = array())
        {
                $this->setOptions($params, 'missing');

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $archive = new Archive($exam);
                        if ($this->_options['verbose']) {
                                $this->flash->notice(sprintf("Finding archive for exam %d", $exam->id));
                        }
                        if (!$archive->exists()) {
                                $this->flash->warning($exam->id);
                        }
                }
        }

        /**
         * Verify exam archives action.
         * @param array $params
         */
        public function verifyAction($params = array())
        {
                $this->setOptions($params, 'verify');

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $archive = new Archive($exam);
                        if ($this->_options['verbose']) {
                                $this->flash->notice(sprintf("Finding archive for exam %d", $exam->id));
                        }
                        if ($archive->verify() == false) {
                                $this->flash->warning($exam->id);
                        }
                }
        }

        /**
         * Get exams to process.
         * @return SimpleResultSet
         * @throws Exception
         */
        private function getExams()
        {
                if ($this->_options['exam']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "id = :exam: AND published = 'Y'",
                                    'bind'       => array('exam' => $this->_options['exam'])
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->_options['all']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "published = 'Y'"
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->_options['start'] && $this->_options['end']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "starttime BETWEEN :start: AND :end: AND published = 'Y'",
                                    'bind'       => array(
                                            'start' => $this->_options['start'],
                                            'end'   => $this->_options['end']
                                    )
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->_options['start']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "starttime > :date: AND published = 'Y'",
                                    'bind'       => array('date' => $this->_options['start'])
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->_options['end']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "starttime < :date: AND published = 'Y'",
                                    'bind'       => array('date' => $this->_options['end'])
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                }

                return $exams;
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
                $this->_options = array('verbose' => false, 'force' => false, 'quiet' => false, 'dry-run' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'create', 'delete', 'update', 'list', 'missing', 'verify', 'start', 'end', 'exam', 'all', 'quiet', 'silent');
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

                if ($this->_options['all']) {
                        $this->_options['exam'] = false;
                        $this->_options['start'] = false;
                        $this->_options['end'] = false;
                }
                if ($this->_options['exam']) {
                        $this->_options['start'] = false;
                        $this->_options['end'] = false;
                        $this->_options['all'] = false;
                }

                if (!$this->_options['exam'] &&
                    !$this->_options['start'] &&
                    !$this->_options['end']) {
                        $this->_options['all'] = true;
                }

                if ($this->_options['silent']) {
                        $this->_options['quiet'] = true;
                        $this->_options['verbose'] = false;
                }
        }

}
