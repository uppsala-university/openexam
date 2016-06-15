<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UUID.php
// Created: 2014-12-02 15:25:21
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior;

use Phalcon\Mvc\ModelInterface;

/**
 * Universally unique identifier (UUID) generator.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UUID extends ModelBehavior
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {
                        $this->trustedContextCall(function($caller) use($model, $options) {

                                $name = $options['field'];

                                if ($options['force']) {
                                        $model->$name = self::generate();
                                } elseif (!isset($model->$name)) {
                                        $model->$name = self::generate();
                                }

                                return true;
                        }, $model->getDI());
                }
        }

        private static function generate()
        {
                $hash = md5(uniqid("", true));
                $uuid = sprintf("%s-%s-%s-%s-%s", substr($hash, 0, 8), substr($hash, 9, 4), substr($hash, 13, 4), substr($hash, 18, 4), substr($hash, 21));
                return $uuid;
        }

}
