<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    FiIterText.php
// Created: 2016-01-12 12:14:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior\Transform;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * Filter text fields.
 * 
 * Content filtering is better handled on client side with intemmidate 
 * knowledge on its own objects/data. Doing this on client side also distribute
 * the work load natural.
 * 
 * @deprecated since version 3.0
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class FilterText extends ModelBehavior
{

        /**
         * Matches MS Word 9+ comments.
         */
        const PATTERN_OPENXML_COMMENT = "/<\!--\[if gte mso \d+\]>.*<\!\[endif\]-->/smU";

        public function notify($type, ModelInterface $model)
        {
                // 
                // Strip comments from text fields:
                // 
                if (($options = $this->getOptions($type))) {
                        // 
                        // Use default pattern:
                        // 
                        if (!isset($options['patterns'])) {
                                $patterns = array(self::PATTERN_OPENXML_COMMENT);
                        } elseif (is_string($options['patterns'])) {
                                $patterns = array($options['patterns']);
                        } else {
                                $patterns = $options['patterns'];
                        }

                        if (is_string($options['fields'])) {
                                $fields = array($options['fields']);
                        } else {
                                $fields = $options['fields'];
                        }

                        // 
                        // Strip text in all fields:
                        // 
                        foreach ($fields as $name) {
                                foreach ($patterns as $pattern) {
                                        $model->$name = preg_replace($pattern, "", $model->$name);
                                }
                        }

                        return true;
                }
        }

}
