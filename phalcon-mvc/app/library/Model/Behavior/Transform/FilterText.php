<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
