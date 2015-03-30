<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Student.php
// Created: 2014-12-12 16:56:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Core\Pattern;

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
         * @param \OpenExam\Models\Student $model The target model.
         */
        public function notify($type, $model)
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
                                if (preg_match(Pattern::PERSNR, $model->user, $matched)) {
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
                $result = $di->get('catalog')->getPrincipal(
                    $persnr, Principal::ATTR_PNR, array('attr' => Principal::ATTR_PN)
                );

                if (count($result) != 0) {
                        return $result[0]->principal;
                } else {
                        return $user;
                }
        }

}
