<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    ImportPingPongText.php
// Created: 2015-04-15 00:19:13
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Questions;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use function _;

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

        private $_stream;

        public function __construct($accept = "")
        {
                parent::__construct(self::ACCEPT);
        }

        public function open()
        {
                $this->_stream = fopen($this->_file, "r");
        }

        private function next()
        {
                if (!($str = fgets($this->_stream))) {
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
                fclose($this->_stream);
        }

}
