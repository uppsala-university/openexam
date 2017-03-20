<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderPdfDocument.php
// Created: 2017-01-31 02:46:45
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Render\Command;

use OpenExam\Library\Console\Command;
use OpenExam\Library\Render\Renderer;

/**
 * PDF document render class.
 *
 * <code>
 * $pages = array(
 *      array('page'  => 'http://www.google.com/'),
 *      array('page'  => 'https://se.yahoo.com')
 * );
 * 
 * // 
 * // Save PDF document to file:
 * // 
 * $render = new RenderPdfDocument();
 * $render->save('/tmp/output.pdf', $pages);
 * 
 * // 
 * // Send PDF document to stdout:
 * // 
 * $render = new RenderPdfDocument();
 * $render->send('file.pdf', $pages);
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RenderPdfDocument implements Renderer
{

        /**
         * The command.
         * @var string 
         */
        private $_command;

        /**
         * Constructor.
         * @param string $command The command string.
         */
        public function __construct($command)
        {
                $this->_command = $command;
        }

        /**
         * Save rendered PDF document.
         * 
         * @param string $filename The output file.
         * @param array $objects PDF object settings.
         * @return bool
         */
        public function save($filename, $objects)
        {
                $execute = sprintf("%s %s %s", $this->_command, self::getPages($objects), $filename);
                $command = new Command($execute);

                return $command->execute();
        }

        /**
         * Send rendered PDF document.
         * 
         * @param string $filename The suggested filename.
         * @param array $objects PDF object settings.
         * @param bool $headers Output HTTP headers.
         * @return bool
         */
        public function send($filename, $objects, $headers = true)
        {
                if ($headers) {
                        header(sprintf('Content-type: %s', 'application/pdf'));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }

                $execute = sprintf("%s %s -", $this->_command, self::getPages($objects));
                $command = new Command($execute);
                $command->setBuffered(false);

                return $command->execute();
        }

        /**
         * Extract pages from settings array.
         * @param array $objects The PDF objects.
         * @return string
         */
        private static function getPages($objects)
        {
                $pages = "";

                foreach ($objects as $obj) {
                        $pages .= " \"" . $obj['page'] . "\"";
                }

                return $pages;
        }

}
