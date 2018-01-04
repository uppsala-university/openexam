<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    RenderTask.php
// Created: 2014-10-28 03:07:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Render\Command\RenderImage as RenderImageCommand;
use OpenExam\Library\Render\Command\RenderPdfDocument as RenderPdfDocumentCommand;
use OpenExam\Library\Render\Extension\RenderImage as RenderImageExtension;
use OpenExam\Library\Render\Extension\RenderPdfDocument as RenderPdfDocumentExtension;
use OpenExam\Library\Render\Queue\RenderQueue;
use OpenExam\Library\Render\Queue\RenderWorker;
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Render;

/**
 * Render service task.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class RenderTask extends MainTask implements TaskInterface
{

        /**
         * Command line options.
         * @var array 
         */
        private $_options;

        public static function getUsage()
        {
                return array(
                        'header'   => 'Render service for HTML -> PDF/Image',
                        'action'   => '--render',
                        'usage'    => array(
                                '--pdf|--image --output=file --page=url',
                                '--worker [--output=file] [--pidfile=path] [--sleep=sec]',
                                '--queue --page=url --exam=id [--poll]'
                        ),
                        'options'  => array(
                                '--pdf'          => 'Render PDF document',
                                '--image'        => 'Render image file',
                                '--output=file'  => 'Save output to file.',
                                '--page=url'     => 'Render local file or URL (can be used multiple times if --type=pdf).',
                                '--globals'      => 'Start input of global render options (can be used multiple times).',
                                '--type=mime'    => 'Type of image (png, jpg, bmp or svg).',
                                '--method=type'  => 'Use render method (command, extension or service).',
                                '--worker'       => 'Run render queue worker process.',
                                '--pidfile=path' => 'Write PID to path (for --worker only).',
                                '--sleep=sec'    => 'Sleep interval between polling.',
                                '--queue'        => 'Add render queue job.',
                                '--exam=id'      => 'Use exam (for --queue only).',
                                '--poll'         => 'Poll for completion (for --queue only).',
                                '--force'        => 'Force action even if already applied.',
                                '--verbose'      => 'Be more verbose.',
                                '--dry-run'      => 'Just print whats going to be done.'
                        ),
                        'aliases'  => array(
                                '--png'       => 'Same as --type=png',
                                '--jpg'       => 'Same as --type=jpg',
                                '--jpeg'      => 'Same as --type=jpg',
                                '--bmp'       => 'Same as --type=bmp',
                                '--svg'       => 'Same as --type=svg',
                                '--command'   => 'Same as --method=command',
                                '--extension' => 'Same as --method=extension',
                                '--service'   => 'Same as --method=service'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Render local file as PNG image',
                                        'command' => '--image --type=png --output=result.png --page=input.html'
                                ),
                                array(
                                        'descr'   => 'Render pages as PDF document',
                                        'command' => '--pdf --page=input1.html --page=input2.html --globals --p1=v1 --p2=v2 --output=result.pdf'
                                ),
                                array(
                                        'descr'   => 'Render Google using the system render service',
                                        'command' => '--pdf --page=http://www.google.se --output=google.pdf --globals --page-size a4 --grayscale'
                                ),
                                array(
                                        'descr'   => 'Start a render queue worker process',
                                        'command' => '--worker --output=file.log'
                                ),
                                array(
                                        'descr'   => 'Log worker events to stderr',
                                        'command' => '--worker --output=php://stderr'
                                ),
                                array(
                                        'descr'   => 'Add job to render queue',
                                        'command' => '--queue --page=http://www.google.se'
                                ),
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
         * Render image file action.
         * @param array $params
         */
        public function imageAction($params = array())
        {
                $this->setOptions($params, 'image');

                if ($this->_options['extension']) {
                        $render = new RenderImageExtension($this->_options['globals']);
                        $render->save($this->_options['output'], array('in' => $this->_options['page'][0]['page']));
                }
                if ($this->_options['command']) {
                        $render = new RenderImageCommand("wkhtmltoimage");
                        $render->setOptions($this->_options['globals']);
                        $render->save($this->_options['output'], array('in' => $this->_options['page'][0]['page']));
                }
                if ($this->_options['service']) {
                        $render = $this->render->getRender('image');
                        $render->setOptions($this->_options['globals']);
                        $render->save($this->_options['output'], array('in' => $this->_options['page'][0]['page']));
                }
        }

        /**
         * Render PDF document action.
         * @param array $params
         */
        public function pdfAction($params = array())
        {
                $this->setOptions($params, 'pdf');

                if ($this->_options['extension']) {
                        $render = new RenderPdfDocumentExtension($this->_options['globals']);
                        $render->save($this->_options['output'], $this->_options['page']);
                }
                if ($this->_options['command']) {
                        $render = new RenderPdfDocumentCommand("wkhtmltopdf");
                        $render->setOptions($this->_options['globals']);
                        $render->save($this->_options['output'], $this->_options['page']);
                }
                if ($this->_options['service']) {
                        $render = $this->render->getRender('pdf');
                        $render->setOptions($this->_options['globals']);
                        $render->save($this->_options['output'], $this->_options['page']);
                }
        }

        /**
         * Render worker process action.
         * @param array $params
         */
        public function workerAction($params = array())
        {
                $this->setOptions($params, 'worker');

                $service = $this->render->getRender(Renderer::FORMAT_PDF);
                $logfile = $this->_options['output'];

                $worker = new RenderWorker($service, $logfile);
                $worker->setInterval($this->_options['sleep']);
                $worker->writePid($this->_options['pidfile']);
                $worker->process();
        }

        /**
         * Render queue action.
         * @param array $params
         */
        public function queueAction($params = array())
        {
                $this->setOptions($params, 'queue');

                $page = $this->_options['page'][0]['page'];
                $exam = $this->_options['exam'];

                $this->user->setPrimaryRole('render');

                $queue = new RenderQueue();
                $jobid = $queue->addJob($exam, Render::TYPE_EXTERN, $page);

                if ($this->_options['poll']) {
                        pollJobResult($jobid);
                }
        }

        /**
         * Poll for render job to complete.
         * @param int $jobid The job ID.
         */
        private function pollJobResult($jobid)
        {
                $done = false;

                while (!$done) {
                        if (!($status = $queue->getStatus($jobid))) {
                                $this->flash->error("Failed get status of job $jobid");
                                break;
                        }

                        if ($this->_options['verbose']) {
                                $this->flash->notice(print_r($status->dump(), true));
                        }

                        switch ($status->status) {
                                case Render::STATUS_FAILED:
                                        $this->flash->notice("The job has failed");
                                        $done = true;
                                        break;
                                case Render::STATUS_FINISH:
                                        $this->flash->notice("The job has finished");
                                        $done = true;
                                        break;
                                case Render::STATUS_MISSING:
                                        $this->flash->notice("The job is missing");
                                        $done = true;
                                        break;
                                case Render::STATUS_QUEUED:
                                        $this->flash->notice("The job has been queued");
                                        break;
                                case Render::STATUS_RENDER:
                                        $this->flash->notice("The job is rendering");
                                        break;
                                default:
                                        $this->flash->warning(sprintf("Unhandled status %s of job $jobid", $status->status));
                        }

                        sleep($this->_options['sleep']);
                        $this->flash->notice("Polling...");
                }

                if ($this->_options['verbose']) {
                        $this->flash->success(print_r($status->dump(), true));
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
                $this->_options = array('verbose' => false, 'force' => false, 'dry-run' => false, 'page' => array(), 'globals' => array(), 'command' => true, 'sleep' => 1, 'pidfile' => '/var/run/openexam-render-worker.pid');

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'type', 'page', 'output', 'globals', 'png', 'jpg', 'jpeg', 'bmp', 'svg', 'method', 'extension', 'command', 'service', 'worker', 'queue', 'exam', 'poll', 'sleep', 'pidfile');
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
                                if (!is_array($this->_options[$option])) {
                                        $this->_options[$option] = true;
                                }
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                if ($current == 'page') {
                                        $this->_options[$current][][$current] = $option;
                                } elseif ($current == 'globals') {
                                        $this->_options[$current][$option] = array_shift($params);
                                } else {
                                        $this->_options[$current] = $option;
                                }
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }

                if ($this->_options['extension']) {
                        $this->_options['command'] = false;
                        $this->_options['service'] = false;
                }
                if ($this->_options['service']) {
                        $this->_options['command'] = false;
                        $this->_options['extension'] = false;
                }
        }

}
