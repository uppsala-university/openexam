<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UnitTestTask.php
// Created: 2014-09-15 12:56:46
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Security\User;

/**
 * Unit test task.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UnittestTask extends MainTask
{

        /**
         * The sample data location.
         * @var string 
         */
        private $sample;
        /**
         * Backup copy of existing file.
         * @var string 
         */
        private $backup;

        /**
         * Initializer hook.
         */
        public function initialize()
        {
                $this->sample = sprintf("%s/unittest/sample.dat", $this->config->application->cacheDir);
                $this->backup = sprintf("%s_%s", $this->sample, time());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Prepare database for running unit test by inserting some data.',
                        'action'   => '--unittest',
                        'usage'    => array(
                                '--setup',
                                '--clean'
                        ),
                        'options'  => array(
                                '--setup'          => 'Add sample data for running unit tests.',
                                '--user=name'      => 'Set username for sample data. Defaults to process owner (calling user).',
                                '--clean[=sample]' => 'Remove sample data (optional specifying sample file).',
                                '--verbose'        => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Insert sample data',
                                        'command' => '--setup --verbose'
                                ),
                                array(
                                        'descr'   => 'Cleanup sample data using last sample',
                                        'command' => '--clean --verbose'
                                ),
                                array(
                                        'descr'   => 'Cleanup sample data using distinguished sample file',
                                        'command' => '--clean=phalcon-mvc/cache/unittest/sample1.dat'
                                )
                        )
                );
        }

        /**
         * Display usage information.
         */
        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        /**
         * Cleanup sample data.
         * @param array $params Task action parameters.
         */
        public function cleanAction($params = array())
        {
                $options = array(
                        'verbose' => false
                );

                for ($i = 0; $i < count($params); ++$i) {
                        if ($params[$i] == 'verbose') {
                                $options['verbose'] = true;
                        } else {
                                $this->sample = $params[$i];
                        }
                }

                if ($options['verbose']) {
                        $this->flash->notice("Using sample data " . $this->sample);
                }
                if (!file_exists($this->sample)) {
                        $this->flash->error("Sample data is missing.\n");
                        return;
                }

                if ($options['verbose']) {
                        $this->flash->notice("Removing sample data from database:");
                        $this->flash->notice("-------------------------------");
                }

                $data = array_reverse(unserialize(file_get_contents($this->sample)));
                foreach ($data as $m => $a) {
                        $class = sprintf("OpenExam\Models\%s", ucfirst($m));
                        $model = $class::findFirst(array('id' => $a['id']));

                        if ($options['verbose']) {
                                $this->flash->notice(sprintf("Model %s:\n", $class));
                                $this->flash->notice(print_r($model->dump(), true));
                                $this->flash->write();
                        }

                        if ($model->delete() == false) {
                                $this->flash->warning("Failed remove sample data from " . $model->getSource());
                        }
                }
        }

        /**
         * Setup sample data.
         * @param array $params Task action parameters.
         */
        public function setupAction($params = array())
        {
                $options = array(
                        'verbose' => false,
                        'user'    => $this->getDI()->getUser()->getPrincipalName()
                );

                for ($i = 0; $i < count($params); ++$i) {
                        if ($params[$i] == 'verbose') {
                                $options['verbose'] = true;
                        }
                        if ($params[$i] == 'user') {
                                $options['user'] = $params[++$i];
                        }
                }

                $this->getDI()->setUser(new User($options['user']));

                $data = array(
                        'exam'        => array(
                                'name'    => 'Exam 1',
                                'creator' => $options['user'],
                                'orgunit' => 'orgunit1',
                                'grades'  => json_encode(array('data' => array('U' => 0, 'G' => 20, 'VG' => 30)))
                        ),
                        'contributor' => array(
                                'exam_id' => 0,
                                'user'    => $options['user']
                        ),
                        'decoder'     => array(
                                'exam_id' => 0,
                                'user'    => $options['user']
                        ),
                        'invigilator' => array(
                                'exam_id' => 0,
                                'user'    => $options['user']
                        ),
                        'student'     => array(
                                'exam_id' => 0,
                                'user'    => $options['user'],
                                'code'    => '1234ABCD'
                        ),
                        'resource'    => array(
                                'exam_id' => 0,
                                'name'    => 'Name1',
                                'path'    => '/tmp/path',
                                'type'    => 'video',
                                'subtype' => 'mp4',
                                'user'    => $options['user']
                        ),
                        'room'        => array(
                                'name' => 'Room 1'
                        ),
                        'computer'    => array(
                                'room_id'  => 0,
                                'ipaddr'   => '127.0.0.1',
                                'port'     => 666,
                                'password' => 'password1',
                        ),
                        'lock'        => array(
                                'computer_id' => 0,
                                'exam_id'     => 0
                        ),
                        'topic'       => array(
                                'exam_id'   => 0,
                                'name'      => 'Topic 1',
                                'randomize' => 0
                        ),
                        'question'    => array(
                                'exam_id'  => 0,
                                'topic_id' => 0,
                                'score'    => 1.0,
                                'name'     => 'Question 1',
                                'quest'    => 'Question text',
                                'user'     => $options['user'],
                        ),
                        'corrector'   => array(
                                'question_id' => 0,
                                'user'        => $options['user']
                        ),
                        'answer'      => array(
                                'question_id' => 0,
                                'student_id'  => 0
                        ),
                        'result'      => array(
                                'answer_id'    => 0,
                                'corrector_id' => 0,
                                'score'        => 1.5
                        ),
                        'file'        => array(
                                'answer_id' => 0,
                                'name'      => 'Name1',
                                'path'      => '/tmp/path',
                                'type'      => 'video',
                                'subtype'   => 'mp4'
                        ),
                        'admin'       => array(
                                'user' => $options['user']
                        ),
                        'teacher'     => array(
                                'user' => $options['user']
                        ),
                        'session'     => array(
                                'session_id' => md5(time()),
                                'data'       => serialize(array('auth' => array('user' => $options['user'], 'type' => 'cas'))),
                                'created'    => time()
                        )
                );

                if ($options['verbose']) {
                        $this->flash->notice("Adding sample data in database:");
                        $this->flash->notice("-------------------------------");
                }
                foreach ($data as $m => $a) {
                        foreach (array(
                            'exam'      => 'exam_id',
                            'room'      => 'room_id',
                            'computer'  => 'computer_id',
                            'topic'     => 'topic_id',
                            'question'  => 'question_id',
                            'student'   => 'student_id',
                            'answer'    => 'answer_id',
                            'corrector' => 'corrector_id') as $n => $fk)
                                if (isset($a[$fk]) && $a[$fk] == 0) {
                                        $a[$fk] = $data[$n]['id'];
                                }
                        $class = sprintf("OpenExam\Models\%s", ucfirst($m));
                        $model = new $class();
                        if (!$model->save($a)) {
                                $this->flash->error(sprintf("Failed save model for %s", $model->getSource()));
                                foreach ($model->getMessages() as $message) {
                                        $this->flash->error($message);
                                }
                                return false;
                        }
                        $data[$m] = $model->dump();
                        if ($options['verbose']) {
                                $this->flash->notice(sprintf("Model %s:\n", $class));
                                $this->flash->notice(print_r($data[$m], true));
                                $this->flash->write();
                        }
                }

                if (!file_exists(dirname($this->sample))) {
                        if ($options['verbose']) {
                                $this->flash->notice("Creating sample data directory " . dirname($this->sample));
                        }
                        if (!mkdir(dirname($this->sample), 0755, true)) {
                                $this->flash->error("Failed create sample directory.");
                        }
                }

                if (file_exists($this->sample)) {
                        copy($this->sample, $this->backup);
                }

                if (!file_put_contents($this->sample, serialize($data))) {
                        $this->flash->error("Failed save sample data");
                } elseif ($options['verbose']) {
                        $this->flash->success("Wrote sample data to " . $this->sample);
                }
        }

}
