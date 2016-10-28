<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Button.php
// Created: 2016-10-28 02:22:32
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Gui\Component;

use OpenExam\Library\Gui\Component;

/**
 * Button component.
 * 
 * @property string $text Button caption/label.
 * @property string $icon The icon name.
 * @property string $color The button color.
 * @property string $target The target action (link).
 * @property array $attrs Optional attributes for anchor element.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Button implements Component
{

        /**
         * Constructor.
         * @param string $text Button caption/label.
         * @param string $icon The icon name.
         * @param string $color The button color.
         */
        public function __construct($text = "Button", $icon = "gear", $color = "primary")
        {
                $this->text = $text;
                $this->icon = $icon;
                $this->color = $color;
        }

        public function render()
        {
                if (!isset($this->target)) {
                        $this->target = "#";
                }
                if (!isset($this->attrs)) {
                        $this->attrs = array();
                }

                printf("<a href=\"%s\" %s>\n", $this->target, implode(" ", $this->attrs));
                printf("<span class=\"label label-%s\">\n", $this->color);
                printf("<i class=\"fa fa-%s\"></i>\n", $this->icon);
                printf("</span>\n");
                printf("</a>\n");
        }

}
