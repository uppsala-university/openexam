<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderImage.php
// Created: 2017-01-31 02:21:27
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Render\Command;

use OpenExam\Library\Console\Command;
use OpenExam\Library\Render\Renderer;

/**
 * Image render class.
 * 
 * <code>
 * $render = new RenderImage('wkhtmltoimage --format png');
 * $render->send('google.png', array('in' => 'http://www.google.se', true));
 * </code>
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RenderImage implements Renderer
{

        private static $_mime = array(
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml'
        );
        /**
         * The command.
         * @var string 
         */
        private $_command;
        /**
         * The MIME type.
         * @var string 
         */
        private $_format;

        /**
         * Constructor.
         * @param string $command The command string.
         */
        public function __construct($command)
        {
                $this->_command = $command;
                $this->_format = 'png';
        }

        /**
         * Set the image format.
         * @param string $format Image format.
         */
        public function setFormat($format)
        {
                if (array_key_exists($format, self::$_mime)) {
                        $this->_format = $format;
                }
        }

        /**
         * Save rendered image.
         * 
         * @param string $filename The output file.
         * @param array $settings The image settings.
         * @return bool
         */
        public function save($filename, $settings)
        {
                $execute = sprintf("%s %s %s", $this->_command, $settings['in'], $filename);
                $command = new Command($execute);

                return $command->execute();
        }

        /**
         * Send rendered image.
         * 
         * @param string $filename The suggested filename.
         * @param array $settings The image settings.
         * @param bool $headers Output HTTP headers.
         * @return bool
         */
        public function send($filename, $settings, $headers = true)
        {
                if ($headers) {
                        header(sprintf('Content-type: %s', self::$_mime[$settings['fmt']]));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }

                $execute = sprintf("%s %s -", $this->_command, $settings['in']);
                $command = new Command($execute);

                return $command->execute();
        }

}
