<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Helper.php
// Created: 2014-11-06 17:50:05
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Gui;

use Phalcon\Mvc\User\Component;

/**
 * Views helper component
 * Formats data to be easily useable in views
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class Helper extends Component
{

        /**
         * Format data returned from catalog service to make it useable in views,
         * in a better and clean way. 
         *
         * @param string $principal
         * @param string $attribute
         * @param string $firstOnly Return only first record, if set true
         * @return array
         * @deprecated since version 2.0.0
         */
        public function getCatalogAttribute($principal, $attribute)
        {
                if (is_null($principal)) {
                        return "";
                }

                if (($result = $this->catalog->getAttribute($principal, $attribute))) {
                        $data = current($result);
                        $attr = $data[$attribute][0];

                        unset($data);
                        return $attr;
                }
        }

}
