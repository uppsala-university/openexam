<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    SimulateTask.php
// Created: 2016-01-13 13:49:37
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Models\Answer;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Session;
use OpenExam\Models\Student;
use Phalcon\Mvc\Model\Resultset;
use const BASE_DIR;

/**
 * System load task.
 *
 * Use this task to simulation load on the server. It has three run modes:
 * 
 * <ul>
 * <li>Natural: Simulate normal student interaction.</li>
 * <li>Torture: Hammer the server.</li>
 * <li>Custom:  Follow custom read/write options.</li>
 * </ul>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SimulateTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;
        /**
         * Fetched exam data.
         * @var array 
         */
        private $_data;

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Simulate system load.',
                        'action'   => '--simulate',
                        'usage'    => array(
                                '--setup [--students=num] [--questions=num]',
                                '--run --exam=id [student=str [--session=str]] [options...]',
                                '--script --exam=id [options...]',
                                '--compile --result=file',
                                '--defaults'
                        ),
                        'options'  => array(
                                '--setup'          => 'Create exam for simulation.',
                                '--run'            => 'Run single student simulation.',
                                '--script'         => 'Create simulation script.',
                                '--compile'        => 'Compile result from previous simulation',
                                '--defaults'       => 'Show default options.',
                                '--output=file'    => 'Generate script file (for --script option).',
                                '--result=file'    => 'Simulation result file.',
                                '--target=url'     => 'Target server URL (defaults to this app on localhost).',
                                '--exam=id'        => 'Use exam ID.',
                                '--session=str'    => 'Use cookie string for authentication.',
                                '--student=user'   => 'Run simulation as student user.',
                                '--students=num'   => 'Add num students on exam.',
                                '--questions=num'  => 'Insert num questions (setup only).',
                                '--natural'        => 'Run normal user interface simulation.',
                                '--torture'        => 'Run in torture mode (no sleep).',
                                '--read'           => 'Generate answer read load.',
                                '--write'          => 'Generate answer write load.',
                                '--start=num:wait' => 'Client startup options (for --script option).',
                                '--sleep=sec'      => 'Second to sleep between server requests.',
                                '--duration=sec'   => 'Duration of simulation.',
                                '--verbose'        => 'Be more verbose.',
                                '--debug'          => 'Print debug information',
                                '--dry-run'        => 'Just print whats going to be done.',
                                '--quite'          => 'Be quiet.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Create exam with 20 questions',
                                        'command' => '--setup --questions=20'
                                ),
                                array(
                                        'descr'   => 'Create exam with 250 students and 12 questions',
                                        'command' => '--setup --questions=12 --students=250'
                                ),
                                array(
                                        'descr'   => 'Create simulation script',
                                        'command' => '--script --exam=123 --natural --start=30:1 --sleep=10 --duration=180 --result=file.dat --output=file.sh'
                                ),
                                array(
                                        'descr'   => 'Simulate a real world application (calling user)',
                                        'command' => '--run --exam=123 --natural --sleep=10 --duration=120'
                                ),
                                array(
                                        'descr'   => 'Simulate a real world application (using username)',
                                        'command' => '--run --exam=123 --natural --sleep=10 --duration=120 --student=xxx'
                                ),
                                array(
                                        'descr'   => 'Run torture test and write result to file',
                                        'command' => '--run --exam=123 --torture --duration=300 --student=xxx --result=file.dat'
                                ),
                                array(
                                        'descr'   => 'Run custom read/write simulation for 5 min',
                                        'command' => '--run --exam=123 --read --write --sleep=1 --duration=300 --student=xxx'
                                ),
                                array(
                                        'descr'   => 'Compile result from previous simulation',
                                        'command' => '--compile --result=file.dat'
                                )
                        )
                );
        }

        /**
         * Setup sample data.
         * @param array $params Task action parameters.
         */
        public function setupAction($params = array())
        {
                $this->setOptions($params, 'setup');

                if (!($exam = $this->addExam())) {
                        return false;
                }

                $students = $this->addStudents($exam);
                $questions = $this->addQuestions($exam);

                foreach ($students as $student) {
                        $this->addAnswers($student, $questions);
                        $this->addSession($student);
                }

                if (!$this->_options['quiet']) {
                        $this->flash->success(sprintf("Exam %d successful setup", $exam->id));
                }
        }

        /**
         * Run simulation.
         * @param array $params Task action parameters.
         */
        public function runAction($params = array())
        {
                $this->setOptions($params, 'run');
                $this->_options['url'] = sprintf("%s/ajax/core", $this->_options['target']);

                if (!$this->_options['session']) {
                        $this->_options['session'] = $this->getSession($this->_options['student']);
                }
                if ($this->_options['verbose'] && !$this->_options['quiet']) {
                        $this->flash->notice(sprintf("Running until %s using %d sec pause between requests.", strftime("%c", $this->_options['endtime']), $this->_options['sleep']));
                }

                // 
                // Get exam data (exam, questions, answers, ...):
                // 
                $data = $this->getExamData();

                // 
                // Generate dummy text and initialize statistics:
                // 
                $text = array();
                $stat = array();

                for ($text = array(), $size = 1024, $max = 512 * 1024; $size <= $max; $size *= 2) {
                        $text[] = array('len' => $size, 'str' => str_repeat("X", $size));
                        $stat[$size]['r'] = array('tmin' => 60, 'tmax' => 0, 'time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                        $stat[$size]['w'] = array('tmin' => 60, 'tmax' => 0, 'time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                }
                $stat['avarage']['r'] = array('tmin' => 60, 'tmax' => 0, 'time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                $stat['avarage']['w'] = array('tmin' => 60, 'tmax' => 0, 'time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);

                // 
                // Run simulation:
                // 
                if ($this->_options['natural']) {
                        $this->runNatural($data, $stat);
                } elseif ($this->_options['torture']) {
                        $this->runTorture($data, $text, $stat);
                } else {
                        $this->runCustom($data, $text, $stat);
                }

                $this->showStatistics($stat);
                $this->saveStatistics($stat);
        }

        /**
         * Generate runtime script.
         * @param array $params Task action parameters.
         */
        public function scriptAction($params = array())
        {
                $this->setOptions($params, 'script');

                if (!$this->_options['exam']) {
                        $this->flash->error("Required parameter --exam is missing");
                        return false;
                }
                if (!($students = $this->getStudents())) {
                        return false;
                }

                if (!($handle = fopen($this->_options['output'], "w"))) {
                        $this->flash->error("Failed open output script");
                        return false;
                }

                list($start, $wait) = explode(":", $this->_options['start']);

                fprintf($handle, "#!/bin/bash\n\n");
                fprintf($handle, "# options=\"--verbose --debug\"\n\n");

                for ($i = 0; $i < count($students); ++$i) {
                        $student = $students[$i];
                        $command = sprintf("php %s/script/simulate.php --run --student=%s", BASE_DIR, $student->user);
                        foreach ($this->_options as $key => $val) {
                                if ($key == 'script' || $key == 'output' ||
                                    $key == 'endtime' || $key == 'student' ||
                                    $key == 'students') {
                                        continue;
                                } elseif (is_bool($val) && $val == false) {
                                        continue;
                                } else {
                                        $command .= " --$key=$val";
                                }
                        }
                        fprintf($handle, "%s \$options &\n", $command);
                        if ($i % $start == 0 && $i != 0) {
                                fprintf($handle, "sleep $wait\n");  // be nice ;-)
                        }
                }

                fclose($handle);

                if (!$this->_options['quiet']) {
                        $this->flash->success(sprintf("Created script %s", $this->_options['output']));
                }
        }

        /**
         * Compile and present result from previous simulation.
         * @param array $params Task action parameters.
         */
        public function compileAction($params = array())
        {
                $this->setOptions($params, 'compile');

                if (!file_exists($this->_options['result'])) {
                        $this->flash->error("Input file is missing");
                        return false;
                }

                if (!($data = $this->readStatistics($this->_options['result']))) {
                        return false;
                }

                $stat = array();
                for ($i = 0; $i < count($data); ++$i) {
                        foreach ($data[$i] as $k1 => $v1) {
                                if (!isset($stat[$k1])) {
                                        $stat[$k1] = array();
                                }
                                foreach ($v1 as $k2 => $v2) {
                                        if (!isset($stat[$k1][$k2])) {
                                                $stat[$k1][$k2] = array('tmin' => 60, 'tmax' => 0, 'time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                                        }
                                        foreach ($v2 as $k3 => $v3) {
                                                if ($k3 == 'mean') {
                                                        continue;
                                                }

                                                switch ($k3) {
                                                        case 'tmin':
                                                                if ($v3 < $stat[$k1][$k2][$k3]) {
                                                                        $stat[$k1][$k2][$k3] = $v3;
                                                                }
                                                                break;
                                                        case 'tmax':
                                                                if ($v3 > $stat[$k1][$k2][$k3]) {
                                                                        $stat[$k1][$k2][$k3] = $v3;
                                                                }
                                                                break;
                                                        default:
                                                                $stat[$k1][$k2][$k3] += $v3;
                                                                break;
                                                }
                                        }
                                }
                        }
                }

                foreach ($stat as $k1 => $v1) {
                        foreach ($v1 as $k2 => $v2) {
                                $stat[$k1][$k2]['mean'] = $stat[$k1][$k2]['time'] / $stat[$k1][$k2]['count'];
                        }
                }

                print_r($stat);
        }

        /**
         * Show default options.
         */
        public function defaultsAction()
        {
                $this->setOptions(array(), 'defaults');
                $this->flash->success(print_r($this->_options, true));
        }

        /**
         * Add new exam.
         * @return Exam|boolean
         */
        private function addExam()
        {
                $exam = new Exam();
                $exam->assign(array(
                        'name'    => 'Simulation',
                        'creator' => $this->_options['student'],
                        'orgunit' => 'Organization',
                        'grades'  => '{U:0,G:50,VG:75}'
                ));
                if (!$this->_options['dry-run']) {
                        if (!$exam->save()) {
                                $this->flash->error(sprintf("Failed save exam: %s", current($exam->getMessages())));
                                return false;
                        }
                }
                if ($this->_options['debug'] && !$this->_options['quiet']) {
                        $this->flash->notice(print_r($exam->toArray(), true));
                }

                return $exam;
        }

        /**
         * Add student on exam.
         * @param Exam $exam The exam model.
         * @return Student[]
         */
        private function addStudents($exam)
        {
                $students = array();

                for ($i = 1; $i <= $this->_options['students']; ++$i) {
                        $student = new Student();
                        $student->assign(array(
                                'exam_id' => $exam->id,
                                'user'    => sprintf("user%d", $i)
                        ));
                        if (!$this->_options['dry-run']) {
                                if (!$student->save()) {
                                        $this->flash->error(sprintf("Failed save student: %s", current($student->getMessages())));
                                        continue;
                                }
                        }
                        if ($this->_options['debug'] && !$this->_options['quiet']) {
                                $this->flash->notice(print_r($student->toArray(), true));
                        }

                        $students[] = $student;
                }

                return $students;
        }

        /**
         * Add questions on exam.
         * @param Exam $exam The exam model.
         * @return Question[]
         */
        private function addQuestions($exam)
        {
                $questions = array();

                // 
                // Add questions and answers:
                // 
                for ($i = 0; $i < $this->_options['questions']; ++$i) {
                        $question = new Question();
                        $question->assign(array(
                                'exam_id'  => $exam->id,
                                'topic_id' => $exam->topics[0]->id,
                                'user'     => $this->_options['student'],
                                'score'    => 1,
                                'name'     => sprintf("Q%d", $i + 1),
                                'quest'    => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc.\n\nCurabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.'
                        ));
                        if (!$this->_options['dry-run']) {
                                if (!$question->save()) {
                                        $this->flash->error(sprintf("Failed save question: %s", current($question->getMessages())));
                                        continue;
                                }
                        }
                        if ($this->_options['debug'] && !$this->_options['quiet']) {
                                $this->flash->notice(print_r($question->toArray(), true));
                        }

                        $questions[] = $question;
                }

                return $questions;
        }

        /**
         * Add answers for student.
         * @param Student $student The student model.
         * @param Question[] $questions The question models.
         */
        private function addAnswers($student, $questions)
        {
                foreach ($questions as $question) {
                        $answer = new Answer();
                        $answer->assign(array(
                                'question_id' => $question->id,
                                'student_id'  => $student->id
                        ));
                        if (!$this->_options['dry-run']) {
                                if (!$answer->save()) {
                                        $this->flash->error(sprintf("Failed save answer: %s", current($answer->getMessages())));
                                        continue;
                                }
                        }
                        if ($this->_options['debug'] && !$this->_options['quiet']) {
                                $this->flash->notice(print_r($answer->toArray(), true));
                        }
                }
        }

        /**
         * Add fake session for student.
         * @param Student $student The student model.
         * @param int $expires The session expires offset from now in seconds.
         */
        private function addSession($student, $expires = 28800)
        {
                // 
                // Required for generation of session ID and encoding:
                // 
                session_id(md5($student->user));

                // 
                // Session data:
                // 
                $_SESSION['auth'] = array(
                        'user'   => $student->user,
                        'type'   => 'simulate',
                        'expire' => time() + $expires,
                        'remote' => '::1'
                );

                // 
                // Insert session in database:
                // 
                $session = new Session();
                $session->assign(array(
                        'session_id' => session_id(),
                        'data'       => session_encode(),
                        'created'    => time()
                ));
                if (!$session->save()) {
                        $this->flash->error(sprintf("Failed save session: %s", current($session->getMessages())));
                } elseif ($this->_options['verbose'] && !$this->_options['quiet']) {
                        $this->flash->success(sprintf("%s\t%s", $student->user, $session->session_id));
                }
        }

        /**
         * Get exam data from model.
         * @return array
         */
        private function getExamData()
        {
                // 
                // Get student model:
                // 
                if (!($student = Student::findFirst(array(
                            'conditions' => 'exam_id = :exam: and user = :user:',
                            'bind'       => array(
                                    'exam' => $this->_options['exam'],
                                    'user' => $this->_options['student']
                            )
                        )
                    ))) {
                        $this->flash->error(sprintf("Failed find student, exiting now (pid=%d, stud=%s)", getmypid(), $this->_options['student']));
                        exit(1);
                }

                // 
                // Get exam data:
                // 
                $data = array(
                        'e' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'exam/read',
                                'data' => array('id' => $this->_options['exam'])
                            )
                        ),
                        't' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'topic/read',
                                'data' => array('exam_id' => $this->_options['exam'])
                            )
                        ),
                        'q' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'question/read',
                                'data' => array('exam_id' => $this->_options['exam'])
                            )
                        ),
                        'a' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'answer/read',
                                'data' => array('student_id' => $student->id)
                            )
                        ),
                );

                //
                // Sanity check:
                // 
                foreach ($data as $key => $val) {
                        if (is_bool($val) && $val == false) {
                                $this->flash->error(sprintf("Failed initialize data, exiting now (pid=%d, stud=%s)", getmypid(), $student->user));
                                exit(1);
                        }
                }

                // 
                // Re-index answers to use question ID as key:
                // 
                foreach ($data['a'] as $key => $val) {
                        $data['a'][$val->question_id] = $val;
                        unset($data['a'][$key]);
                }

                $this->_data = $data;
                return $data;
        }

        /**
         * Get session ID.
         * @param string $user The user principal name.
         * @return string
         */
        private function getSession($user)
        {
                if (!($session = Session::findFirst("data LIKE '%$user%'"))) {
                        $this->flash->error("failed find session of $user");
                } else {
                        return $session->session_id;
                }
        }

        /**
         * Get all students on this exam.
         * @return Resultset
         */
        private function getStudents()
        {
                if (!($students = Student::find(sprintf("exam_id = %d", $this->_options['exam'])))) {
                        $this->flash->error("failed get students");
                } else {
                        return $students;
                }
        }

        /**
         * Get filtered statistics.
         * @param array $stat The collected statistics.
         * @return array
         */
        private function getStatistics($stat)
        {
                // 
                // Remove unused slots:
                // 
                foreach ($stat as $key => $arr) {
                        if ($stat[$key]['r']['count'] == 0) {
                                unset($stat[$key]['r']);
                        }
                        if ($stat[$key]['w']['count'] == 0) {
                                unset($stat[$key]['w']);
                        }
                        if (count($stat[$key]) == 0) {
                                unset($stat[$key]);
                        }
                }

                // 
                // Compute request mean value for each size:
                // 
                foreach ($stat as $key => $arr) {
                        if (is_numeric($key)) {
                                $stat[$key]['r']['mean'] = $stat[$key]['r']['time'] / $stat[$key]['r']['count'];
                                $stat[$key]['w']['mean'] = $stat[$key]['w']['time'] / $stat[$key]['w']['count'];
                        } else {
                                $stat['avarage']['r']['mean'] = $stat['avarage']['r']['time'] / $stat['avarage']['r']['count'];
                                $stat['avarage']['w']['mean'] = $stat['avarage']['w']['time'] / $stat['avarage']['w']['count'];
                        }
                }

                return $stat;
        }

        /**
         * Show simulation statistics.
         * @param array $stat The collected statistics.
         */
        private function showStatistics($stat)
        {
                if (!$this->_options['quiet']) {
                        $this->flash->success(print_r($this->getStatistics($stat), true));
                }
        }

        /**
         * Read statistics collected by script.
         * @param string $file The result file.
         * @return array
         */
        private function readStatistics($file)
        {
                $result = array();

                if (!($handle = fopen($file, "r"))) {
                        $this->flash->error("Failed open result file");
                        return false;
                }
                while (($line = fgets($handle))) {
                        $data = unserialize(trim($line));
                        $result[] = $data;
                }
                if (!fclose($handle)) {
                        $this->flash->error("Failed close result file");
                        return false;
                }

                return $result;
        }

        /**
         * Save simulation statistics.
         * @param array $stat The collected statistics.
         */
        private function saveStatistics($stat)
        {
                $file = $this->_options['result'];
                $user = $this->_options['student'];
                $stat = $this->getStatistics($stat);

                // 
                // Use lockfile to ensure exclusive access during write.
                // 

                if ($file) {
                        if (!($handle = fopen($file, "a"))) {
                                $this->flash->error("Failed open result file");
                                return false;
                        }
                        if (!flock($handle, LOCK_EX)) {
                                $this->flash->error("Failed lock result file");
                                return false;
                        }

                        if (!fwrite($handle, serialize($stat) . "\n")) {
                                $this->flash->error("Failed write result file");
                                return false;
                        }
                        if (!fflush($handle)) {
                                $this->flash->error("Failed flush result file");
                                return false;
                        }

                        if (!flock($handle, LOCK_UN)) {
                                $this->flash->error("Failed unlock result file");
                                return false;
                        }

                        if (!fclose($handle)) {
                                $this->flash->error("Failed close result file");
                                return false;
                        }
                }
        }

        /**
         * Run torture test.
         * @param array $data The exam and related data.
         * @param array $text Sample text messages in various sizes.
         * @param array $stat Output statistics (reference).
         */
        private function runTorture($data, $text, &$stat)
        {
                $qmax = count($data['q']);
                $tmax = count($text) - 1;

                $qind = 0;
                $tind = 0;

                while (time() < $this->_options['endtime']) {
                        if ($qind == $qmax) {
                                $tind = 0;
                                $qind = 0;
                        }

                        $answer = $data['a'][$data['q'][$qind]->id];
                        $answer->answer = $text[$tind]['str'];

                        $this->saveAnswer($answer, $text[$tind], $stat);
                        $this->readAnswer($answer, $text[$tind], $stat);

                        if ($tind++ == $tmax) {
                                $tind = 0;
                                $qind++;
                        }
                }
        }

        /**
         * Run custom test.
         * @param array $data The exam and related data.
         * @param array $text Sample text messages in various sizes.
         * @param array $stat Output statistics (reference).
         */
        private function runCustom($data, $text, &$stat)
        {
                while (time() < $this->_options['endtime']) {
                        $index = rand(0, count($data['q']) - 1);
                        $tsize = rand(0, count($text) - 1);

                        $answer = $data['a'][$data['q'][$index]->id];
                        $answer->answer = $text[$tsize]['str'];

                        if ($this->_options['write']) {
                                $this->saveAnswer($answer, $text[$tsize], $stat);
                        }

                        if ($this->_options['read']) {
                                $this->readAnswer($answer, $text[$tsize], $stat);
                        }

                        if ($this->_options['sleep'] != 0) {
                                sleep($this->_options['sleep']);
                        }
                }
        }

        /**
         * Simulate normal student interaction.
         * @param array $data The exam and related data.
         * @param array $stat Output statistics (reference).
         */
        private function runNatural($data, &$stat)
        {
                // 
                // Reset saved answers:
                // 
                foreach ($data['a'] as $answer) {
                        $answer->answer = "";
                        $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'answer/update',
                                'data' => (array) $answer
                        ));
                }

                $smax = 6;
                $snum = 6;
                $qind = 0;

                $qdat = null;
                $adat = null;

                while (time() < $this->_options['endtime']) {
                        // 
                        // Switch question after $smax savings:
                        // 
                        if ($snum++ == $smax) {
                                $snum = 0;
                                $qind = rand(0, count($data['q']) - 1);
                                $qdat = $data['q'][$qind];
                                $adat = $data['a'][$data['q'][$qind]->id];

                                $stime = microtime(true);
                                $this->sendRequest(array(
                                        'role' => 'student',
                                        'path' => 'question/read',
                                        'data' => array($qdat->id)
                                ));
                                $etime = microtime(true);
                                $ttime = $etime - $stime;       // total                                
                                $this->setStatistics($stat, strlen($qdat->quest), $ttime, 'r', 'avarage');

                                $this->sendRequest(array(
                                        'role' => 'student',
                                        'path' => 'answer/read',
                                        'data' => array($adat->id)
                                ));
                                $etime = microtime(true);
                                $ttime = $etime - $stime;       // total
                                $this->setStatistics($stat, strlen($adat->answer), $ttime, 'r', 'avarage');
                        }

                        // 
                        // Write answer:
                        // 
                        $adat->answer = str_pad($adat->answer, rand(10, 60), "X");

                        $stime = microtime(true);
                        $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'answer/update',
                                'data' => (array) $adat
                        ));
                        $etime = microtime(true);
                        $ttime = $etime - $stime;       // total
                        $this->setStatistics($stat, strlen($adat->answer), $ttime, 'w', 'avarage');

                        sleep($this->_options['sleep']);
                }
        }

        /**
         * Save statistics.
         * @param array $stat The collected statistics.
         * @param int $bytes Number rof bytes read or write.
         * @param float $time Elapsed time.
         * @param string $mode The read/write mode ('r' or 'w').
         * @param string|int $key The section key.
         */
        private function setStatistics(&$stat, $bytes, $time, $mode, $key)
        {
                $stat[$key][$mode]['time'] += $time;
                $stat[$key][$mode]['count'] ++;
                $stat[$key][$mode]['bytes'] += $bytes;

                if ($time < $stat[$key][$mode]['tmin']) {
                        $stat[$key][$mode]['tmin'] = $time;
                }
                if ($time > $stat[$key][$mode]['tmax']) {
                        $stat[$key][$mode]['tmax'] = $time;
                }
        }

        /**
         * Send write answer request.
         * @param object $answer The answer object.
         * @param array $text Sample text messages in various sizes.
         * @param array $stat Output statistics (reference).
         */
        private function saveAnswer($answer, $text, &$stat)
        {
                $stime = microtime(true);
                $result = $this->sendRequest(array(
                        'role' => 'student',
                        'path' => 'answer/update',
                        'data' => (array) $answer
                ));
                $etime = microtime(true);
                if (!$result) {
                        $stat[$text['len']]['w']['failed'] ++;
                        $stat['avarage']['w']['failed'] ++;
                } else {
                        $this->setStatistics($stat, $text['len'], $etime - $stime, 'w', $text['len']);
                        $this->setStatistics($stat, $text['len'], $etime - $stime, 'w', 'avarage');
                }
        }

        /**
         * Send read answer request.
         * @param object $answer The answer object.
         * @param array $text Sample text messages in various sizes.
         * @param array $stat Output statistics (reference).
         */
        private function readAnswer($answer, $text, &$stat)
        {
                $stime = microtime(true);
                $result = $this->sendRequest(array(
                        'role' => 'student',
                        'path' => 'answer/read',
                        'data' => array('id' => $answer->id)
                ));
                $etime = microtime(true);
                if (!$result) {
                        $stat[$text['len']]['r']['failed'] ++;
                        $stat['avarage']['r']['failed'] ++;
                } elseif ($result->answer != $answer->answer) {
                        $this->flash->error(sprintf("Answer truncated for id=%d and size=%d", $answer->id, $text['len']));
                } else {
                        $this->setStatistics($stat, $text['len'], $etime - $stime, 'r', $text['len']);
                        $this->setStatistics($stat, $text['len'], $etime - $stime, 'r', 'avarage');
                }
        }

        /**
         * Send AJAX request.
         * @param array $params The payload data.
         * @return boolean|array
         */
        private function sendRequest($params)
        {
                // 
                // Initialize cURL:
                // 
                if (!($this->curl = curl_init())) {
                        $this->flash->error("cURL failed initilize");
                        return false;
                }
                curl_setopt($this->curl, CURLOPT_COOKIE, sprintf("PHPSESSID=%s", $this->_options['session']));
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

                // 
                // Timeout and transfer limit before failing:
                // 
                curl_setopt($this->curl, CURLOPT_TIMEOUT, 60);
                curl_setopt($this->curl, CURLOPT_LOW_SPEED_LIMIT, 1);
                curl_setopt($this->curl, CURLOPT_LOW_SPEED_TIME, 60);

                // 
                // Set AJAX request options:
                // 
                curl_setopt($this->curl, CURLOPT_URL, sprintf("%s/%s/%s", $this->_options['url'], $params['role'], $params['path']));
                curl_setopt($this->curl, CURLOPT_POST, 1);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params['data']);

                // 
                // Send request:
                // 
                if (!($result = curl_exec($this->curl))) {
                        $this->flash->error(sprintf("cURL [execute]: %s (%s - %d)", curl_error($this->curl), curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL), curl_getinfo($this->curl, CURLINFO_HTTP_CODE)));
                        $this->flash->error(sprintf("cURL [execute]: data=[%s]", json_encode($params['data'])));
                        curl_close($this->curl);
                        return false;
                } else {
                        $result = json_decode($result);
                }

                if ($this->_options['debug'] && !$this->_options['quiet']) {
                        $this->flash->notice(print_r($result, true));
                } else if ($this->_options['verbose'] && !$this->_options['quiet']) {
                        $this->flash->notice("Requested $params[path] using role $params[role]");
                }

                if (isset($result->failed)) {
                        $this->flash->error(sprintf("cURL [response]: %s", $result->failed->return));
                        curl_close($this->curl);
                        return false;
                } else {
                        curl_close($this->curl);
                        return $result->success->return;
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
                $this->_options = array('verbose' => false, 'debug' => false, 'dry-run' => false, 'quiet' => false, 'read' => false, 'write' => false, 'natural' => false, 'torture' => false, 'script' => false, 'output' => 'simulate.sh', 'result' => false, 'target' => false, 'questions' => 12, 'start' => '30:1', 'sleep' => 10, 'duration' => 120, 'students' => 1);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'debug', 'dry-run', 'quiet', 'setup', 'student', 'students', 'questions', 'run', 'exam', 'read', 'write', 'natural', 'torture', 'script', 'output', 'result', 'target', 'session', 'start', 'sleep', 'duration');
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

                if (!$this->_options['student']) {
                        $this->_options['student'] = $this->getDI()->getUser()->getPrincipalName();
                } else {
                        $this->_options['impersonate'] = true;
                }
                if (!$this->_options['target']) {
                        $this->_options['target'] = sprintf("http://localhost/%s", $this->config->application->baseUri);
                }

                $this->_options['endtime'] = time() + $this->_options['duration'];
        }

}
