<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
use OpenExam\Models\Student;

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

        private $options;

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
                                '--setup --student=username [--questions=num]',
                                '--run --ajax --exam=id [--read] [--write] [--session=str] [--torture] [--sleep=sec] [--duration=sec]'
                        ),
                        'options'  => array(
                                '--setup'         => 'Create exam.',
                                '--run'           => 'Run simulation.',
                                '--session=str'   => 'Use cookie string for authentication.',
                                '--exam=id'       => 'Use exam ID.',
                                '--student=user'  => 'The student username.',
                                '--questions=num' => 'Insert num questions (setup only).',
                                '--natural'       => 'Simulate normal user interface load.',
                                '--torture'       => 'Run in torture mode (no sleep).',
                                '--read'          => 'Generate read load.',
                                '--write'         => 'Generate write load.',
                                '--sleep=sec'     => 'Pause sleep second between each iteration.',
                                '--duration=sec'  => 'Number of seconds to run.',
                                '--verbose'       => 'Be more verbose.',
                                '--debug'         => 'Print debug information',
                                '--dry-run'       => 'Just print whats going to be done.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Create exam with 20 questions',
                                        'command' => '--setup --student=user@example.com --questions=20'
                                ),
                                array(
                                        'descr'   => 'Simulate a real world application',
                                        'command' => '--session=xxx --exam=123 --natural --sleep=10 --duration=120'
                                ),
                                array(
                                        'descr'   => 'Run torture test',
                                        'command' => '--session=xxx --exam=123 --torture'
                                ),
                                array(
                                        'descr'   => 'Run custom read/write simulation for 5 min',
                                        'command' => '--session=xxx --exam=123 --read --write --sleep=1 --duration=300'
                                ),
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

                $exam = $this->addExam();
                $stud = $this->addStudent($exam);
                $this->addQuestions($exam, $stud);

                if ($this->options['verbose']) {
                        $this->flash->success(sprintf("Exam %d successful setup", $exam->id));
                }
        }

        private function addExam()
        {
                $exam = new Exam();
                $exam->assign(array(
                        'name'    => 'Simulation',
                        'creator' => $this->options['student'],
                        'orgunit' => 'Organization',
                        'grades'  => '{U:0,G:50,VG:75}'
                ));
                if (!$this->options['dry-run']) {
                        if (!$exam->save()) {
                                $this->flash->error(sprintf("Failed save exam: %s", current($exam->getMessages())));
                                return false;
                        }
                }
                if ($this->options['debug']) {
                        $this->flash->notice(print_r($exam->toArray(), true));
                }

                return $exam;
        }

        private function addStudent($exam)
        {
                $student = new Student();
                $student->assign(array(
                        'exam_id' => $exam->id,
                        'user'    => $this->options['student']
                ));
                if (!$this->options['dry-run']) {
                        if (!$student->save()) {
                                $this->flash->error(sprintf("Failed save student: %s", current($student->getMessages())));
                                return false;
                        }
                }
                if ($this->options['debug']) {
                        $this->flash->notice(print_r($student->toArray(), true));
                }

                return $student;
        }

        private function addQuestions($exam, $student)
        {
                // 
                // Add questions and answers:
                // 
                for ($i = 0; $i < $this->options['questions']; ++$i) {
                        $question = new Question();
                        $question->assign(array(
                                'exam_id'  => $exam->id,
                                'topic_id' => $exam->topics[0]->id,
                                'user'     => $this->options['student'],
                                'score'    => 1,
                                'name'     => sprintf("Q%d", $i + 1),
                                'quest'    => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc.\n\nCurabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.'
                        ));
                        if (!$this->options['dry-run']) {
                                if (!$question->save()) {
                                        $this->flash->error(sprintf("Failed save question: %s", current($question->getMessages())));
                                        return false;
                                }
                        }
                        if ($this->options['debug']) {
                                $this->flash->notice(print_r($question->toArray(), true));
                        }

                        $answer = new Answer();
                        $answer->assign(array(
                                'question_id' => $question->id,
                                'student_id'  => $student->id
                        ));
                        if (!$this->options['dry-run']) {
                                if (!$answer->save()) {
                                        $this->flash->error(sprintf("Failed save answer: %s", current($answer->getMessages())));
                                        return false;
                                }
                        }
                        if ($this->options['debug']) {
                                $this->flash->notice(print_r($answer->toArray(), true));
                        }
                }
        }

        /**
         * Setup sample data.
         * @param array $params Task action parameters.
         */
        public function runAction($params = array())
        {
                $this->setOptions($params, 'run');
                $this->options['url'] = sprintf("http://localhost/%s/ajax/core", $this->config->application->baseUri);

                $this->curl = curl_init();
                curl_setopt($this->curl, CURLOPT_COOKIE, sprintf("PHPSESSID=%s", $this->options['session']));
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

                if ($this->options['verbose']) {
                        $this->flash->notice(sprintf("Running until %s using %d sec pause between requests.", strftime("%c", $this->options['endtime']), $this->options['sleep']));
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
                        $stat[$size]['r'] = array('time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                        $stat[$size]['w'] = array('time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                }
                $stat['avarage']['r'] = array('time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);
                $stat['avarage']['w'] = array('time' => 0, 'count' => 0, 'failed' => 0, 'bytes' => 0);

                // 
                // Run simulation:
                // 
                if ($this->options['natural']) {
                        $this->runNatural($data, $stat);
                } elseif ($this->options['torture']) {
                        $this->runTorture($data, $text, $stat);
                } else {
                        $this->runCustom($data, $text, $stat);
                }

                curl_close($this->curl);

                $this->showStatistics($stat);
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
                $student = current($this->sendRequest(array(
                            'role' => 'admin',
                            'path' => 'student/read',
                            'data' => array('user' => $this->options['student'], 'exam_id' => $this->options['exam'])
                        )
                ));

                // 
                // Get exam data:
                // 
                $data = array(
                        'e' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'exam/read',
                                'data' => array('id' => $this->options['exam'])
                            )
                        ),
                        't' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'topic/read',
                                'data' => array('exam_id' => $this->options['exam'])
                            )
                        ),
                        'q' => $this->sendRequest(array(
                                'role' => 'student',
                                'path' => 'question/read',
                                'data' => array('exam_id' => $this->options['exam'])
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
                // Re-index answers to use question ID as key:
                // 
                foreach ($data['a'] as $key => $val) {
                        $data['a'][$val->question_id] = $val;
                        unset($data['a'][$key]);
                }

                return $data;
        }

        /**
         * Show simulation statistics.
         * @param array $stat The collected statistics.
         */
        private function showStatistics($stat)
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
                                $stat[$key]['r']['request'] = $stat[$key]['r']['time'] / $stat[$key]['r']['count'];
                                $stat[$key]['w']['request'] = $stat[$key]['w']['time'] / $stat[$key]['w']['count'];
                        } else {
                                $stat['avarage']['r']['request'] = $stat['avarage']['r']['time'] / $stat['avarage']['r']['count'];
                                $stat['avarage']['w']['request'] = $stat['avarage']['w']['time'] / $stat['avarage']['w']['count'];
                        }
                }

                // 
                // Use simple display method:
                // 
                $this->flash->success(print_r($stat, true));
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

                while (time() < $this->options['endtime']) {
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
                while (time() < $this->options['endtime']) {
                        $index = rand(0, count($data['q']) - 1);
                        $tsize = rand(0, count($text) - 1);

                        $answer = $data['a'][$data['q'][$index]->id];
                        $answer->answer = $text[$tsize]['str'];

                        if ($this->options['write']) {
                                $this->saveAnswer($answer, $text[$tsize], $stat);
                        }

                        if ($this->options['read']) {
                                $this->readAnswer($answer, $text[$tsize], $stat);
                        }

                        if ($this->options['sleep'] != 0) {
                                sleep($this->options['sleep']);
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

                while (time() < $this->options['endtime']) {
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
                                $this->sendRequest(array(
                                        'role' => 'student',
                                        'path' => 'answer/read',
                                        'data' => array($adat->id)
                                ));
                                $etime = microtime(true);

                                $stat['avarage']['r']['time'] += $etime - $stime;
                                $stat['avarage']['r']['count'] ++;
                                $stat['avarage']['r']['bytes'] += strlen($adat->answer);
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

                        $stat['avarage']['w']['time'] += $etime - $stime;
                        $stat['avarage']['w']['count'] ++;
                        $stat['avarage']['w']['bytes'] += strlen($adat->answer);

                        sleep($this->options['sleep']);
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
                        $stat[$text['len']]['w']['time'] += $etime - $stime;
                        $stat[$text['len']]['w']['count'] ++;
                        $stat[$text['len']]['w']['bytes'] += $text['len'];      // approx
                        $stat['avarage']['w']['time'] += $etime - $stime;
                        $stat['avarage']['w']['count'] ++;
                        $stat['avarage']['w']['bytes'] += $text['len'];         // approx
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
                        $stat[$text['len']]['r']['time'] += $etime - $stime;
                        $stat[$text['len']]['r']['count'] ++;
                        $stat[$text['len']]['r']['bytes'] += $text['len'];      // approx
                        $stat['avarage']['r']['time'] += $etime - $stime;
                        $stat['avarage']['r']['count'] ++;
                        $stat['avarage']['r']['bytes'] += $text['len'];         // approx
                }
        }

        /**
         * Send AJAX request.
         * @param array $params The payload data.
         * @return boolean|array
         */
        private function sendRequest($params)
        {
                if ($params['role'] != 'admin') {
                        $impersonate = $this->options['impersonate'];
                } else {
                        $impersonate = false;
                }

                if ($impersonate) {
                        curl_setopt($this->curl, CURLOPT_URL, sprintf("%s/%s/%s?impersonate=%s", $this->options['url'], $params['role'], $params['path'], $this->options['student']));
                } else {
                        curl_setopt($this->curl, CURLOPT_URL, sprintf("%s/%s/%s", $this->options['url'], $params['role'], $params['path']));
                }

                curl_setopt($this->curl, CURLOPT_POST, 1);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params['data']);

                if (!($result = curl_exec($this->curl))) {
                        $this->flash->error(curl_error($this->curl));
                        return false;
                } else {
                        $result = json_decode($result);
                }

                if ($this->options['debug']) {
                        $this->flash->notice(print_r($result, true));
                } else if ($this->options['verbose']) {
                        $this->flash->notice("Requested $params[path] using role $params[role]");
                }

                if (isset($result->failed)) {
                        $this->flash->error($result->failed->return);
                        return false;
                } else {
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
                $this->options = array('verbose' => false, 'debug' => false, 'dry-run' => false, 'read' => false, 'write' => false, 'natural' => false, 'questions' => 12, 'torture' => false, 'sleep' => 10, 'duration' => 120, 'impersonate' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'debug', 'dry-run', 'setup', 'student', 'questions', 'run', 'exam', 'read', 'write', 'natural', 'session', 'torture', 'sleep', 'duration', 'impersonate');
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

                if (!$this->options['student']) {
                        $this->options['student'] = $this->getDI()->getUser()->getPrincipalName();
                } else {
                        $this->options['impersonate'] = true;
                }

                $this->options['endtime'] = time() + $this->options['duration'];
        }

}
