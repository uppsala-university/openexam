<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ResultTask.php
// Created: 2016-04-19 22:00:41
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Core\Result as ResultHandler;
use OpenExam\Models\Exam;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultSet;

/**
 * Downloadable results task.
 * 
 * Use this task to maintain the directory of downloadable result file. It 
 * can be runned periodical to cleanup expired result files or pre-generate
 * files for newly decoded exams.
 *
 * Sensible defaults could be:
 * 
 * o) Remove files older than 4 weeks.
 * o) Pre-generate for last 2 weeks.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ResultTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $options;
        /**
         * The result directory.
         * @var string 
         */
        private $resdir;

        public function __construct()
        {
                $this->resdir = sprintf("%s/results", $this->config->application->cacheDir);
        }

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Management tool for downloadable result files.',
                        'action'   => '--result',
                        'usage'    => array(
                                '--create --days=num|--exam=num [--force]',
                                '--delete --days=num'
                        ),
                        'options'  => array(
                                '--create'   => 'Generate result files.',
                                '--delete'   => 'Remove generated result files.',
                                '--days=num' => 'Work on exams older/newer than num days.',
                                '--exam=num' => 'Use this exam instead of using --days.',
                                '--all'      => 'Process all exams.',
                                '--generate' => 'Alias for --create.',
                                '--remove'   => 'Alias for --delete',
                                '--force'    => 'Force generate files.',
                                '--verbose'  => 'Be more verbose.',
                                '--dry-run'  => 'Just print whats going to be done.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Generate result files for decoded exams newer than two weeks',
                                        'command' => '--create --days=14'
                                ),
                                array(
                                        'descr'   => 'Generate result files for this exam',
                                        'command' => '--create --exam=27386'
                                ),
                                array(
                                        'descr'   => 'Remove result files for exams older than one month',
                                        'command' => '--delete --days=31'
                                ),
                                array(
                                        'descr'   => 'Remove result files for all exams',
                                        'command' => '--delete --all'
                                ))
                );
        }

        /**
         * Create result files action.
         * @param array $params
         */
        public function createAction($params = array())
        {
                $this->setOptions($params, 'create');

                if (!file_exists($this->resdir)) {
                        $this->flash->error("The result directory is missing");
                        return false;
                }
                if ($this->options['verbose']) {
                        $this->flash->notice("Starting result file generation");
                }

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $this->createResults($exam);
                }

                if ($this->options['verbose']) {
                        $this->flash->success("Finished result file generation");
                }
        }

        /**
         * Alias action for create.
         * @param array $params
         */
        public function generateAction($params = array())
        {
                $this->createAction($params);
        }

        /**
         * Remove generated result files action.
         * @param array $params
         */
        public function deleteAction($params = array())
        {
                $this->setOptions($params, 'delete');

                if (!file_exists($this->resdir)) {
                        $this->flash->error("The result directory is missing");
                        return false;
                }
                if ($this->options['verbose']) {
                        $this->flash->notice("Starting result file cleanup");
                }

                $exams = $this->getExams();
                foreach ($exams as $exam) {
                        $this->deleteResults($exam);
                }

                if ($this->options['verbose']) {
                        $this->flash->success("Finished result file cleanup");
                }
        }

        public function removeAction($params = array())
        {
                $this->deleteAction($params);
        }

        /**
         * Create result for this exam.
         * @param Exam $exam The exam object.
         */
        private function createResults($exam)
        {
                $result = new ResultHandler($exam);
                $result->setForced($this->options['force']);

                if ($result->exist() && !$result->getForced()) {
                        if ($this->options['verbose']) {
                                $this->flash->notice(sprintf("++ Skipping exam %d (result directory exists)", $exam->id));
                        }
                        return;
                }

                if ($this->options['verbose']) {
                        $this->flash->notice(sprintf("++ Processing exam %d", $exam->id));
                }

                foreach ($exam->students as $student) {
                        if ($this->options['verbose']) {
                                $this->flash->notice(sprintf("   Generating result for student %d (%s)", $student->id, $student->code));
                        }
                        if (!$this->options['dry-run']) {
                                $result->createFile($student);
                        }
                }
                if ($this->options['verbose']) {
                        $this->flash->notice(sprintf("   Creating zip-file for exam %d", $exam->id));
                }
                if (!$this->options['dry-run']) {
                        $result->createArchive();
                }
        }

        /**
         * Delete results for this exam.
         * @param Exam $exam The exam object.
         */
        private function deleteResults($exam)
        {
                $result = new ResultHandler($exam);

                if (!$result->exist()) {
                        if ($this->options['verbose']) {
                                $this->flash->notice(sprintf("++ Skipping exam %d (result directory missing)", $exam->id));
                        }
                        return;
                }

                if ($this->options['verbose']) {
                        $this->flash->notice(sprintf("++ Processing exam %d", $exam->id));
                }
                foreach ($exam->students as $student) {
                        if ($this->options['verbose']) {
                                $this->flash->notice(sprintf("   Removing result for student %d (%s)", $student->id, $student->code));
                        }
                        if (!$this->options['dry-run']) {
                                $result->delete($student);
                        }
                }
                if ($this->options['verbose']) {
                        $this->flash->notice(sprintf("   Cleanup of exam %d", $exam->id));
                }
                if (!$this->options['dry-run']) {
                        $result->clean();
                }
        }

        /**
         * Get exams to process.
         * @return SimpleResultSet
         * @throws Exception
         */
        private function getExams()
        {
                if ($this->options['exam']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "id = :exam: AND decoded = 'Y'",
                                    'bind'       => array('exam' => $this->options['exam'])
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->options['all']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "decoded = 'Y'"
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->options['create']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "endtime > :date: AND decoded = 'Y'",
                                    'bind'       => array('date' => $this->options['time'])
                            ))) == false) {
                                throw new Exception("Failed get exam models.");
                        }
                } elseif ($this->options['delete']) {
                        if (($exams = Exam::find(array(
                                    'conditions' => "endtime < :date: AND decoded = 'Y'",
                                    'bind'       => array('date' => $this->options['time'])
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
                $this->options = array('verbose' => false, 'force' => false, 'dry-run' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'create', 'delete', 'days', 'exam', 'all', 'remove', 'generate');
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

                if (!$this->options['days'] && !$this->options['exam'] && !$this->options['all']) {
                        throw new Exception("Required option '--days', '--exam' or '--all' is missing.");
                }

                if ($this->options['days']) {
                        $this->options['time'] = strftime(
                            "%Y-%m-%d %H:%M:%S", time() - 24 * 3600 * intval($this->options['days'])
                        );
                }
        }

}
