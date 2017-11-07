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
 * @property string $href The destination URL.
 * @property string $target The target action (i.e. _blank).
 * @property array $attrs Optional attributes for anchor element.
 * 
 * @property boolean $compact Render in compact mode.
 * @property boolean $outline Render in outline mode.
 *
 * @property string $tag Volatile data for button.
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
        public function __construct($text = "Button", $icon = "gear", $color = "grey")
        {
                $this->text = $text;
                $this->icon = $icon;
                $this->color = $color;
        }

        public function render()
        {
                if (!isset($this->href)) {
                        $this->href = "#";
                }
                if (!isset($this->target)) {
                        $this->target = "";
                }
                if (!isset($this->attrs)) {
                        $this->attrs = array();
                }
                if (!isset($this->compact)) {
                        $this->compact = false;
                }
                if (!isset($this->outline)) {
                        $this->outline = false;
                }

                if ($this->compact) {
                        printf("<a href=\"%s\" target=\"%s\" %s title=\"%s\">\n", $this->href, $this->target, implode(" ", $this->attrs), $this->text);
                } else {
                        printf("<a href=\"%s\" target=\"%s\" %s>\n", $this->href, $this->target, implode(" ", $this->attrs));
                }
                if ($this->outline) {
                        printf("<span class=\"toolbtn toolbtn-%s-outline\">\n", $this->color);
                } else {
                        printf("<span class=\"toolbtn toolbtn-%s\">\n", $this->color);
                }
                if ($this->compact) {
                        printf("<i class=\"fa fa-%s\"></i>\n", $this->icon);
                } else {
                        printf("<i class=\"fa fa-%s\"></i>\n%s\n", $this->icon, $this->text);
                }
                printf("</span>\n");
                printf("</a>\n");
        }

}
