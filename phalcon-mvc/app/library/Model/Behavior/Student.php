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
// File:    Student.php
// Created: 2014-12-12 16:56:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Core\Pattern;
use OpenExam\Library\Model\Exception;
use OpenExam\Models\Student as StudentModel;
use Phalcon\Mvc\ModelInterface;

/**
 * Behavior for the student model.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Student extends ModelBehavior
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param StudentModel $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {
                        // 
                        // Generate missing anonymous code:
                        // 
                        if ($options['code']) {
                                if (!isset($model->code)) {
                                        $model->code = $this->getCode($model->user);
                                }
                        }

                        // 
                        // Lookup username from personal number:
                        // 
                        if ($options['user']) {
                                if (Pattern::match(Pattern::MATCH_PERSNR, $model->user, $matched)) {
                                        if (strlen($matched[1]) == 6) {   // short year
                                                if (date('y') < substr($matched[1], 0, 2)) {
                                                        $matched[1] = 19 . $matched[1];
                                                } else {
                                                        $matched[1] = 20 . $matched[1];
                                                }
                                        }
                                        $persnr = sprintf("%s%s", $matched[1], $matched[2]);
                                        $model->user = $this->getUser($model->user, $persnr, $model->getDI());
                                }
                        }
                }
        }

        private function getCode($user)
        {
                return strtoupper(substr(md5(sprintf("%s%d", $user, time())), 0, 8));
        }

        private function getUser($user, $persnr, $di)
        {
                if (($result = $di->get('catalog')->getPrincipal(
                    $persnr, Principal::ATTR_PNR, null, Principal::ATTR_PN
                    ))) {
                        return $result->principal;
                } else {
                        throw new Exception(sprintf("%s was recognized as a personal number, but username could not be looked up.", $user));
                }
        }

}
