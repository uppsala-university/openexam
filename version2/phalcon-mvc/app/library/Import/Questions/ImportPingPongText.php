<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportPingPongText.php
// Created: 2015-04-15 00:19:13
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Questions;

use OpenExam\Library\Core\Error;

/**
 * Questions import from PING-PONG.
 * 
 * This class supports import of question bank from PING-PONG in plain text
 * file format.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportPingPongText extends ImportPingPong
{

        const ACCEPT = "text/plain";
        const DELIMITER = "\t";

        private $stream;

        public function __construct($accept = "")
        {
                parent::__construct(self::ACCEPT);
        }

        public function open()
        {
                $this->stream = fopen($this->file, "r");
        }

        private function next()
        {
                if (!($str = fgets($this->stream))) {
                        return null;
                } else {
                        return explode(self::DELIMITER, $str);
                }
        }

        public function read()
        {
                if (($data = $this->next()) && $data[0] != self::EXPECT) {
                        $message = sprintf(_("Expected header '%s' at index (1,1)"), self::EXPECT);
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }

                if (($data = $this->next()) && $data[1] != self::FORMAT) {
                        $message = sprintf(_("Expected format '%s' at index (2,2)"), self::FORMAT);
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }

                while ($data = $this->next()) {
                        if (count($data) == 0) {
                                continue;
                        } else {
                                parent::append($data[0], $data[1]);
                        }
                }

                parent::read();
        }

        public function close()
        {
                fclose($this->stream);
        }

}
