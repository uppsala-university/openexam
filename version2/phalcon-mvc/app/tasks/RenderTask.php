<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderTask.php
// Created: 2014-10-28 03:07:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Render\RenderImage;
use OpenExam\Library\Render\RenderPdfDocument;

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
                                '--pdf|--image --output=file --page=url'
                        ),
                        'options'  => array(
                                '--pdf'     => 'Render PDF document',
                                '--image'   => 'Render image file',
                                '--output'  => 'Save output to file.',
                                '--page'    => 'Render local file or URL (can be used multiple times if --type=pdf).',
                                '--type'    => 'Type of image (png, jpg, bmp or svg).',
                                '--png'     => 'Alias for --type=png',
                                '--jpg'     => 'Alias for --type=jpg',
                                '--jpeg'    => 'Alias for --type=jpg',
                                '--bmp'     => 'Alias for --type=bmp',
                                '--svg'     => 'Alias for --type=svg',
                                '--force'   => 'Force action even if already applied.',
                                '--verbose' => 'Be more verbose.',
                                '--dry-run' => 'Just print whats going to be done.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Render local file as PNG image:',
                                        'command' => '--image --type=png --output=result.png --page=input.html'
                                ),
                                array(
                                        'descr'   => 'Render pages as PDF document:',
                                        'command' => '--pdf --page=input1.html --page=input2.html --globals --p1=v1 --p2=v2 --output=result.pdf'
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
         * Render image file action.
         * @param array $params
         */
        public function imageAction($params = array())
        {
                $this->setOptions($params, 'image');
                if ($this->_options['type']) {
                        $this->_options['globals']['fmt'] = $this->_options['type'];
                } else {
                        foreach (array('png', 'jpg', 'jpeg', 'bmp', 'svg') as $type) {
                                if ($this->_options[$type]) {
                                        $this->_options['globals']['fmt'] = $type;
                                }
                        }
                }
                $render = new RenderImage($this->_options['globals']);
                $render->save($this->_options['output'], array('in' => $this->_options['page'][0]['page']));
        }

        /**
         * Render PDF document action.
         * @param array $params
         */
        public function pdfAction($params = array())
        {
                $this->setOptions($params, 'pdf');
                $render = new RenderPdfDocument($this->_options['globals']);
                $render->save($this->_options['output'], $this->_options['page']);
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
                $this->_options = array('verbose' => false, 'force' => false, 'dry-run' => false, 'page' => array(), 'globals' => array());

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'type', 'page', 'output', 'globals', 'png', 'jpg', 'jpeg', 'bmp', 'svg');
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
        }

}
