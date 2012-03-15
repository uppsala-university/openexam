<?php

//
// Copyright (C) 2010-2012 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/html.inc
// Author: Anders Lövgren
// Date:   2010-05-30
//

/**
 * Common HTML classes. These classes should give more structured code by
 * providing a object oriented approach to i.e. form and tables.
 *
 * The Component class represent some kind of non-containing tag, for
 * example, it could be a input tag. The Container class can contain other
 * containers or components.
 *
 * Notes:
 *
 * 1. Perhaps should we return the cdata object from the container class, so
 *    that this code is possible:
 *
 *    $data = $row->addData($text);
 *    $link = $data->getText()->addLink($url);
 *
 *      // or maybe:
 *
 *    $data = $row->addData($text);
 *    $data->addLink($data->getText(), $url);
 *
 *    However, this opens up for invalid (or at least unwanted) code like:
 *
 *    $area = new TextArea(...);
 *    $area->getText()->addLink($url);   // This is no good
 *
 * 2. Disclaimer: Not all HTML tags are implemented, and those that are might
 *    have missing attributes. This is intentional, as the goal was to provide
 *    an object oriented framework for the most commonly used HTML tags in a
 *    page body.
 *
 *
 * The javascript events. Note that not all events are supported by all HTML
 * tags. See http: *www.w3schools.com/tags/ref_eventattributes.asp.
 *
 * Using defined contants instead of string helps to detect misspelled event
 * attributes that would be silently ignored by browers.
 * 
 * Event handlers:
 * ----------------------
 * 
 * A few common case event handlers exist (named EVENT_HANDLER_XXX). One of 
 * them serves a deeper discussion, namely the EVENT_HANDLER_CHECK_EMPTY. 
 * 
 * This event handler should be connected with a form like this to perform a 
 * check of empty fields on form submit.
 * 
 * <code>
 * <?php
 * $form = new Form('script.php');
 * $form->setEvent(EVENT_ON_SUBMIT, EVENT_HANDLER_CHECK_EMPTY);
 * ?>
 * </code>
 * 
 * It depends on a pre-defined javascript function. An simple implementation 
 * to be added to an javascript file included in each script might look like 
 * this:
 * 
 * <code>
 * // 
 * // Validate input elements (of type text) and textareas:
 * // 
 * function check_form(form, message) {
 *     for(i = 0; i < form.children.length; i++) {
 *         if(form.children[i].type == 'text' || form.children[i].tagName == 'TEXTAREA') {
 *             if(form.children[i].value == "") {
 *                 form.children[i].value = message;
 *                 form.children[i].focus();
 *                 return false;
 *             }
 *         }
 *     }
 *     return true;
 * }
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @package UserInterface
 * @subpackage HTML
 */
// 
// Body and frameset events:
// 
if (!defined('EVENT_ON_LOAD')) {
        define('EVENT_ON_LOAD', "onload");              // Script to be run when a document load
}
if (!defined('EVENT_ON_UNLOAD')) {
        define('EVENT_ON_UNLOAD', "onunload");          // Script to be run when a document unload
}
// 
// Form events:
// 
if (!defined('EVENT_ON_BLUR')) {
        define('EVENT_ON_BLUR', "onblur");              // Script to be run when an element loses focus
}
if (!defined('EVENT_ON_CHANGE')) {
        define('EVENT_ON_CHANGE', "onchange");          // Script to be run when an element change
}
if (!defined('EVENT_ON_FOCUS')) {
        define('EVENT_ON_FOCUS', "onfocus");            // Script to be run when an element gets focus
}
if (!defined('EVENT_ON_RESET')) {
        define('EVENT_ON_RESET', "onreset");            // Script to be run when a form is reset
}
if (!defined('EVENT_ON_SELECT')) {
        define('EVENT_ON_SELECT', "onselect");          // Script to be run when an element is selected
}
if (!defined('EVENT_ON_SUBMIT')) {
        define('EVENT_ON_SUBMIT', "onsubmit");          // Script to be run when a form is submitted
}
// 
// Image events:
// 
if (!defined('EVENT_ON_ABORT')) {
        define('EVENT_ON_ABORT', "onabort");            // Script to be run when a form is submitted
}
// 
// Keyboard events:
// 
if (!defined('EVENT_ON_KEY_DOWN')) {
        define('EVENT_ON_KEY_DOWN', "keydown");         // Script to be run when a key is pressed
}
if (!defined('EVENT_ON_KEY_PRESS')) {
        define('EVENT_ON_KEY_PRESS', "keypress");       // Script to be run when a key is pressed and released
}
if (!defined('EVENT_ON_KEY_UP')) {
        define('EVENT_ON_KEY_UP', "keyup");             // Script to be run when a key is released
}
// 
// Mouse events:
// 
if (!defined('EVENT_ON_CLICK')) {
        define('EVENT_ON_CLICK', "onclick");            // Script to be run on a mouse click
}
if (!defined('EVENT_ON_DBL_CLICK')) {
        define('EVENT_ON_DBL_CLICK', "ondblclick");     // Script to be run on a mouse double-click
}
if (!defined('EVENT_ON_DOUBLE_CLICK')) {
        define('EVENT_ON_DOUBLE_CLICK', "ondblclick");  // Script to be run on a mouse double-click
}
if (!defined('EVENT_ON_MOUSE_DOWN')) {
        define('EVENT_ON_MOUSE_DOWN', "onmousedown");   // Script to be run when mouse button is pressed
}
if (!defined('EVENT_ON_MOUSE_MOVE')) {
        define('EVENT_ON_MOUSE_MOVE', "onmousemove");   // Script to be run when mouse pointer moves
}
if (!defined('EVENT_ON_MOUSE_OUT')) {
        define('EVENT_ON_MOUSE_OUT', "onmouseout");     // Script to be run when mouse pointer moves out of an element
}
if (!defined('EVENT_ON_MOUSE_OVER')) {
        define('EVENT_ON_MOUSE_OVER', "onmouseover");   // Script to be run when mouse pointer moves over an element
}
if (!defined('EVENT_ON_MOUSE_UP')) {
        define('EVENT_ON_MOUSE_UP', "onmouseup");       // Script to be run when mouse button is released
}

// 
// Table frame attribute constants:
// 
if (!defined('TABLE_FRAME_VOID')) {
        define('TABLE_FRAME_VOID', "void");
}
if (!defined('TABLE_FRAME_ABOVE')) {
        define('TABLE_FRAME_ABOVE', "above");
}
if (!defined('TABLE_FRAME_BELOW')) {
        define('TABLE_FRAME_BELOW', "below");
}
if (!defined('TABLE_FRAME_HSIDES')) {
        define('TABLE_FRAME_HSIDES', "hsides");
}
if (!defined('TABLE_FRAME_LHS')) {
        define('TABLE_FRAME_LHS', "lhs");
}
if (!defined('TABLE_FRAME_RHS')) {
        define('TABLE_FRAME_RHS', "rhs");
}
if (!defined('TABLE_FRAME_VSIDES')) {
        define('TABLE_FRAME_VSIDES', "vsides");
}
if (!defined('TABLE_FRAME_BOX')) {
        define('TABLE_FRAME_BOX', "box");
}
if (!defined('TABLE_FRAME_BORDER')) {
        define('TABLE_FRAME_BORDER', "border");
}

// 
// Table rules attribute constants:
// 
if (!defined('TABLE_RULES_NONE')) {
        define('TABLE_RULES_NONE', "none");
}
if (!defined('TABLE_RULES_GROUPS')) {
        define('TABLE_RULES_GROUPS', "groups");
}
if (!defined('TABLE_RULES_ROWS')) {
        define('TABLE_RULES_ROWS', "rows");
}
if (!defined('TABLE_RULES_COLS')) {
        define('TABLE_RULES_COLS', "cols");
}
if (!defined('TABLE_RULES_ALL')) {
        define('TABLE_RULES_ALL', "all");
}

// 
// Constants for TBody and TableItem (base class of TableRow, TableHeader 
// and TableData) align and valign attributes:
// 
if (!defined('TABLE_ALIGN_RIGHT')) {
        define('TABLE_ALIGN_RIGHT', "right");
}
if (!defined('TABLE_ALIGN_LEFT')) {
        define('TABLE_ALIGN_LEFT', "left");
}
if (!defined('TABLE_ALIGN_CENTER')) {
        define('TABLE_ALIGN_CENTER', "center");
}
if (!defined('TABLE_ALIGN_JUSTIFY')) {
        define('TABLE_ALIGN_JUSTIFY', "justify");
}
if (!defined('TABLE_ALIGN_CHAR')) {
        define('TABLE_ALIGN_CHAR', "char");
}

if (!defined('TABLE_VALIGN_TOP')) {
        define('TABLE_VALIGN_TOP', "top");
}
if (!defined('TABLE_VALIGN_MIDDLE')) {
        define('TABLE_VALIGN_MIDDLE', "middle");
}
if (!defined('TABLE_VALIGN_BOTTOM')) {
        define('TABLE_VALIGN_BOTTOM', "bottom");
}
if (!defined('TABLE_VALIGN_BASELINE')) {
        define('TABLE_VALIGN_BASELINE', "baseline");
}

// 
// Constants for the scope attribute of TableCell:
// 
if (!defined('TABLE_SCOPE_COL')) {
        define('TABLE_SCOPE_COL', "col");
}
if (!defined('TABLE_SCOPE_ROW')) {
        define('TABLE_SCOPE_ROW', "row");
}
if (!defined('TABLE_SCOPE_COLGROUP')) {
        define('TABLE_SCOPE_COLGROUP', "colgroup");
}
if (!defined('TABLE_SCOPE_ROWGROUP')) {
        define('TABLE_SCOPE_ROWGROUP', "rowgroup");
}

// 
// Link types:
// 
if (!defined('LINK_TYPE_HREF')) {
        define('LINK_TYPE_HREF', "href");
}
if (!defined('LINK_TYPE_NAME')) {
        define('LINK_TYPE_NAME', "name");
}

// 
// Link attributes:
// 
if (!defined('LINK_SHAPE_DEFAULT')) {
        define('LINK_SHAPE_DEFAULT', "default");
}
if (!defined('LINK_SHAPE_RECT')) {
        define('LINK_SHAPE_RECT', "rect");
}
if (!defined('LINK_SHAPE_CIRCLE')) {
        define('LINK_SHAPE_CIRCLE', "circle");
}
if (!defined('LINK_SHAPE_POLY')) {
        define('LINK_SHAPE_POLY', "poly");
}

if (!defined('LINK_TARGET_BLANK')) {
        define('LINK_TARGET_BLANK', "_blank");
}
if (!defined('LINK_TARGET_PARENT')) {
        define('LINK_TARGET_PARENT', "_parent");
}
if (!defined('LINK_TARGET_SELF')) {
        define('LINK_TARGET_SELF', "_self");
}
if (!defined('LINK_TARGET_TOP')) {
        define('LINK_TARGET_TOP', "_top");
}

// 
// Constants for button types.
// 
if (!defined('BUTTON_SUBMIT')) {
        define('BUTTON_SUBMIT', "submit");
}
if (!defined('BUTTON_RESET')) {
        define('BUTTON_RESET', "reset");
}
if (!defined('BUTTON_STANDARD')) {
        define('BUTTON_STANDARD', "standard");
}

// 
// Some handy inline event handlers:
// 
if (!defined('EVENT_HANDLER_CLEAR_CONTENT')) {
        define('EVENT_HANDLER_CLEAR_CONTENT', "javascript:this.value=''");
}
if (!defined('EVENT_HANDLER_CONFIRM_DELETE')) {
        define('EVENT_HANDLER_CONFIRM_DELETE', sprintf("javascript:return confirm('%s');", _("Are you really sure?")));
}
if (!defined('EVENT_HANDLER_CANCEL_BUBBLE')) {  // Stop event propagation
        define('EVENT_HANDLER_CANCEL_BUBBLE', "javascript:this.cancelBubble = true; event.stopPropagation();");
}
if (!defined('EVENT_HANDLER_CHECK_EMPTY')) {
        define('EVENT_HANDLER_CHECK_EMPTY', sprintf("return check_form(this, '%s');", _("This field must be filled in.")));
}

// 
// Output mode for HTML element:
// 
if (!defined('ELEMENT_OUTPUT_COMPONENT')) {
        define('ELEMENT_OUTPUT_COMPONENT', 1);
}
if (!defined('ELEMENT_OUTPUT_CONTAINER_START')) {
        define('ELEMENT_OUTPUT_CONTAINER_START', 2);
}
if (!defined('ELEMENT_OUTPUT_CONTAINER_END')) {
        define('ELEMENT_OUTPUT_CONTAINER_END', 3);
}

//
// Constants for the FORM action:
//
if (!defined('FORM_ACTION_GET')) {
        define('FORM_ACTION_GET', 'GET');
}
if (!defined('FORM_ACTION_POST')) {
        define('FORM_ACTION_POST', 'POST');
}
//
// Allows easy switch between development and production mode:
//
if (!defined('FORM_ACTION_DEFAULT')) {
        define('FORM_ACTION_DEFAULT', FORM_ACTION_GET);
}

// 
// MessageBox icons URL:
// 
if (!defined('MESSAGEBOX_ICON_URL')) {
        define('MESSAGEBOX_ICON_URL', '/openexam/icons');
}
// 
// MessageBox standard titles:
// 
if (!defined('MESSAGEBOX_TITLE_HINT')) {
        define('MESSAGEBOX_TITLE_HINT', _('Hint'));
}
if (!defined('MESSAGEBOX_TITLE_SUCCESS')) {
        define('MESSAGEBOX_TITLE_SUCCESS', _('Success'));
}
if (!defined('MESSAGEBOX_TITLE_INFO')) {
        define('MESSAGEBOX_TITLE_INFO', _('Information'));
}
if (!defined('MESSAGEBOX_TITLE_WARN')) {
        define('MESSAGEBOX_TITLE_WARN', _('Warning'));
}
if (!defined('MESSAGEBOX_TITLE_ERROR')) {
        define('MESSAGEBOX_TITLE_ERROR', _('Error'));
}

/**
 * This class serves as the base class for component and container classes.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Element
{

        private $class;              // The classname (CSS)
        private $tag;                // The tag name (i.e. h2)
        private $id;                 // The unique ID (CSS)
        private $style;              // Inline style
        private $title;              // The title attribute.
        private $event = array();    // Event attributes
        private $attr = array();     // HTML tag attributes

        /**
         * Contruct the HTML tag element.
         *
         * @param string $tag The element name.
         */

        protected function __construct($tag)
        {
                $this->tag = $tag;
        }

        /**
         * Set classname (CSS)
         *
         * @param string $class  The class name.
         */
        public function setClass($class)
        {
                $this->class = $class;
        }

        /**
         * Set unique ID (CSS)
         *
         * @param string $id The ID
         */
        public function setId($id)
        {
                $this->id = $id;
        }

        /**
         * Set inline style.
         *
         * @param string $style
         */
        public function setStyle($style)
        {
                $this->style = $style;
        }

        /**
         * Set optional title (tooltip)
         *
         * @param string $title
         */
        public function setTitle($title)
        {
                $this->title = $title;
        }

        /**
         * Set javascript code for event.
         *
         * @param string $event The event name
         * @param string $code Javascript code.
         */
        public function setEvent($event, $code)
        {
                $this->event[$event] = $code;
        }

        /**
         * Set an HTML attribute (i.e. name or selected).
         *
         * @param string $name
         * @param string $value
         */
        protected function setAttr($name, $value = null)
        {
                $this->attr[$name] = $value;
        }

        /**
         * Get the name of this element.
         * @return string
         */
        public function getName()
        {
                return $this->attr['name'];
        }

        /**
         * Get the unique ID of this element.
         * 
         * @return string 
         */
        public function getId()
        {
                return $this->id;
        }

        /**
         * Output this HTML element. See ELEMENT_OUTPUT_XXX
         *
         * @param int $mode
         */
        protected function output($mode)
        {
                if ($mode == ELEMENT_OUTPUT_CONTAINER_START ||
                    $mode == ELEMENT_OUTPUT_COMPONENT) {
                        printf("<%s", $this->tag);
                        if (isset($this->class)) {
                                printf(" class=\"%s\"", $this->class);
                        }
                        if (isset($this->id)) {
                                printf(" id=\"%s\"", $this->id);
                        }
                        if (isset($this->style)) {
                                printf(" style=\"%s\"", $this->style);
                        }
                        if (count($this->attr) != 0) {
                                foreach ($this->attr as $name => $value) {
                                        if (isset($name)) {
                                                if (isset($value)) {
                                                        printf(" %s=\"%s\"", $name, $value);
                                                } else {
                                                        printf(" %s", $name);
                                                }
                                        }
                                }
                        }
                        if (count($this->event) != 0) {
                                foreach ($this->event as $event => $code) {
                                        printf(" %s=\"%s\"", $event, $code);
                                }
                        }
                        if (isset($this->title)) {
                                printf(" title=\"%s\"", $this->title);
                        }

                        if ($mode == ELEMENT_OUTPUT_COMPONENT) {
                                printf("/>\n");
                        } else {
                                printf(">");
                        }
                }
                if ($mode == ELEMENT_OUTPUT_CONTAINER_END) {
                        printf("</%s>\n", $this->tag);
                }
        }

}

/**
 * An href or name (anchor) link.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Link extends Container
{

        /**
         * Constructor.
         * @param string $type The link type. See LINK_TYPE_XXX
         * @param string $link The resource target.
         */
        public function __construct($type, $link)
        {
                parent::__construct("a");
                parent::setAttr($type, $link);
        }

        /**
         * Set the href attribute.
         * @param string $url The resource target.
         */
        public function setHref($url)
        {
                parent::setAttr("href", $url);
        }

        /**
         * Set the name attribute.
         * @param string $name The resource target.
         */
        public function setName($name)
        {
                parent::setAttr("name", $name);
        }

        /**
         * Set the charset attribute.
         * @param string $encoding The character set encoding.
         */
        public function setCharSet($encoding)
        {
                parent::setAttr("charset", $encoding);
        }

        /**
         * Set the coords attribute.
         * @param string $coords
         */
        public function setCoordinates($coords)
        {
                parent::setAttr("coords", $coords);
        }

        /**
         * Set the hreflang attribute.
         * @param string $lang
         */
        public function setHrefLang($lang)
        {
                parent::setAttr("hreflang", $lang);
        }

        /**
         * Set the rel attribute. This attribute is used by documentatiation
         * parsers to create document relations.
         * @param string $text
         */
        public function setRelation($text)
        {
                parent::setAttr("rel", $text);
        }

        /**
         * Set the rev attribute. This attribute is used by documentatiation
         * parsers to create document relations.
         * @param string $text
         */
        public function setReverse($text)
        {
                parent::setAttr("rev", $text);
        }

        /**
         * Set the shape attribute. See LINK_SHAPE_XXX.
         * @param string $shape
         */
        public function setShape($shape)
        {
                parent::setAttr("shape", $shape);
        }

        /**
         * Set the target attribute. See LINK_TARGET_XXX.
         * @param string $target
         */
        public function setTarget($target)
        {
                parent::setAttr("target", $target);
        }

}

/**
 * This class represent a block of text (CDATA).
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Cdata
{

        const separator = ' ';

        private $text;
        private $link;

        /**
         * Constructor.
         * @param string|array $text The text.
         */
        public function __construct($text)
        {
                self::addText($text);
        }

        /**
         * Append text.
         * @param string|array $text The text to append.
         * @param string $space Add space between this text and previous.
         */
        public function addText($text, $space = self::separator)
        {
                if (isset($this->text)) {
                        $this->text .= $space;
                }
                if (is_array($text)) {
                        $this->text .= implode($space, $text);
                } else {
                        $this->text .= $text;
                }
        }

        /**
         * Set a link to this block of text. This makes the link a container
         * of this object.
         *
         * @param Link $link 
         */
        public function setLink($link)
        {
                $this->link = $link;
                $this->link->setText(&$this->text);
        }

        /**
         * Output HTML for this object.
         */
        public function output()
        {
                if (isset($this->link)) {
                        $this->link->output();
                } else {
                        echo $this->text;
                }
        }

        /**
         * Get this object as string.
         * @return string 
         */
        public function __toString()
        {
                return $this->text;
        }

}

/**
 * Represents a BR element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class BR extends Cdata
{

        /**
         * Constructor.
         */
        public function __construct()
        {
                parent::__construct("<br/>\n");
        }

}

/**
 * Represents a HR element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class HR extends Cdata
{

        /**
         * Constructor.
         */
        public function __construct()
        {
                parent::__construct("<hr/>\n");
        }

}

/**
 * This class represent a non-containing HTML tag.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Component extends Element
{

        private $label;

        /**
         * Output HTML for this object.
         */
        public function output()
        {
                if (isset($this->label)) {
                        $this->label->output();
                }
                parent::output(ELEMENT_OUTPUT_COMPONENT);
        }

        /**
         * Sets a label for this component. Calling this function makes sense
         * only for visual elemements, like textboxes, not for hidden fields.
         *
         * @param string $text
         * @return Label
         */
        public function setLabel($text = null)
        {
                $this->label = new Label($this->getName(), $text);
                return $this->label;
        }

}

/**
 * This class represent a containing tag, like table, form or table row.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Container extends Element
{

        /**
         * The character data object.
         * @var Cdata 
         */
        private $cdata;
        /**
         * Optional label.
         * @var string 
         */
        private $label;
        /**
         * Contained elements.
         * @var type 
         */
        private $childs = array();

        /**
         * Add a child element (component, container or character data (cdata))
         * to this objects child element collection. Returns the newly added
         * object.
         *
         * @param Element|Cdata $child The child element.
         * @return Element|Cdata
         */
        public function addElement($child)
        {
                $this->childs[] = $child;
                return $child;
        }

        /**
         * Get number of immediate child elements.
         * @return int 
         */
        public function count()
        {
                return count($this->childs);
        }

        /**
         * Set a block of text (as cdata).
         * 
         * This function replaces any previous character data. Returns the 
         * new cdata object.
         * 
         * @param string|array $text The character data.
         * @return Cdata 
         */
        public function setText($text)
        {
                $this->cdata = new Cdata($text);
                return $this->cdata;
        }

        /**
         * Append a block of text (as cdata).
         *
         * This function should be called after setText() has been called. It
         * is an error to call this function without a previous call to 
         * setText().
         * 
         * @param string $text The character data.
         * @return Cdata 
         */
        public function addText($text)
        {
                $this->cdata->addText($text);
                return $this->cdata;
        }

        /**
         * Sets a label for this component. Calling this function makes sense
         * only for visual elemements, like textboxes, not for hidden fields.
         *
         * @param string $text The label text.
         * @return Label
         */
        public function setLabel($text = null)
        {
                $this->label = new Label($this->getName(), $text);
                return $this->label;
        }

        /**
         * Set the link object.
         * @param Link $link The link object.
         * @return Link
         */
        public function setLink($link)
        {
                $this->cdata->setLink($link);
                return $link;
        }

        /**
         * Output HTML for this container and its child elements (if any).
         */
        public function output()
        {
                if (isset($this->label)) {
                        $this->label->output();
                }
                parent::output(ELEMENT_OUTPUT_CONTAINER_START);
                if (count($this->childs) > 1) {
                        printf("\n");
                }
                foreach ($this->childs as $child) {
                        $child->output();
                }
                if (isset($this->cdata)) {
                        $this->cdata->output();
                }
                parent::output(ELEMENT_OUTPUT_CONTAINER_END);
        }

}

/**
 * This class represent a label for an element (i.e. a textbox).
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Label extends Container
{

        /**
         * Constructor.
         * @param string $for The name of the buddy element.
         * @param string $text The label text.
         */
        public function __construct($for, $text = null)
        {
                parent::__construct("label");
                parent::setAttr("for", $for);
                parent::setText(isset($text) ? $text . ":" : "&nbsp;");
        }

        /**
         * Output HTML for this label.
         */
        public function output()
        {
                printf("<br/>\n");
                parent::output();
        }

}

/**
 * The standard H1 to H6 header, implemented as text container.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Header extends Container
{

        /**
         * Constructor.
         * @param int $size The header size.
         * @param string $text The header text.
         */
        public function __construct($size, $text)
        {
                parent::__construct(sprintf("h%d", $size));
                parent::setText($text);
        }

}

/**
 * Represent an H1 element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class H1 extends Header
{

        /**
         * Constructor.
         * @param string $text The header text.
         */
        public function __construct($text)
        {
                parent::__construct(1, $text);
        }

}

/**
 * Represent an H2 element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class H2 extends Header
{

        /**
         * Constructor.
         * @param string $text The header text.
         */
        public function __construct($text)
        {
                parent::__construct(2, $text);
        }

}

/**
 * Represent an H3 element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class H3 extends Header
{

        /**
         * Constructor.
         * @param string $text The header text.
         */
        public function __construct($text)
        {
                parent::__construct(3, $text);
        }

}

/**
 * Represent an H4 element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class H4 extends Header
{

        /**
         * Constructor.
         * @param string $text The header text.
         */
        public function __construct($text)
        {
                parent::__construct(4, $text);
        }

}

/**
 * Represent an H5 element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class H5 extends Header
{

        /**
         * Constructor.
         * @param string $text The header text.
         */
        public function __construct($text)
        {
                parent::__construct(5, $text);
        }

}

/**
 * Represent an H6 element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class H6 extends Header
{

        /**
         * Constructor.
         * @param string $text The header text.
         */
        public function __construct($text)
        {
                parent::__construct(6, $text);
        }

}

/**
 * This class represents an paragraph (P) element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Paragraph extends Container
{

        /**
         * Constructor.
         * @param string $text The paragraph text.
         */
        public function __construct($text = null)
        {
                parent::__construct("p");
                parent::setText($text);
        }

}

/**
 * This class represents an image (IMG) element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Image extends Component
{

        /**
         * Constructor.
         * @param string $src The image source (URI).
         * @param string $alt The alt attribute.
         */
        public function __construct($src, $alt)
        {
                parent::__construct("img");
                parent::setAttr("src", $src);
                parent::setAttr("alt", $alt);
        }

        /**
         * Set the height attribute.
         * @param int $pixels
         */
        public function setHeight($pixels)
        {
                parent::setAttr("height", $pixels);
        }

        /**
         * Set the width attribute.
         * @param int $pixels
         */
        public function setWidth($pixels)
        {
                parent::setAttr("width", $pixels);
        }

        /**
         * Set the longdesc attribute.
         * @param string $url
         */
        public function setLongDescription($url)
        {
                parent::setAttr("longdesc", $url);
        }

        /**
         * Set the usemap attribute.
         * @param string $name
         */
        public function setUseMap($name)
        {
                parent::setAttr("usemap", $name);
        }

}

/**
 * A combobox (drop down list) class. This is an abstraction of an select
 * element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Option
 */
class ComboBox extends Container
{

        /**
         * Constructor.
         * @param string $name The combobox name.
         */
        public function __construct($name)
        {
                parent::__construct("select");
                parent::setAttr("name", $name);
        }

        /**
         * Sets the disabled attribute.
         */
        public function setDisabled()
        {
                parent::setAttr("disabled", "disabled");
        }

        /**
         * Sets the multiple attribute.
         */
        public function setMultiple()
        {
                parent::setAttr("multiple", "multiple");
        }

        /**
         * Sets the size attribute. This is the number of items in the list
         * to display at once.
         *
         * @param int $number The number of items.
         */
        public function setVisibleOptions($number)
        {
                parent::setAttr("size", $number);
        }

        /**
         * Add an option (item) to this combobox.
         *
         * @param string $value The option value.
         * @param string $text The option text.
         * @return Option
         */
        public function addOption($value, $text)
        {
                return parent::addElement(new Option($value, $text));
        }

}

/**
 * An option item for the ComboBox class.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see ComboBox
 */
class Option extends Container
{

        /**
         * Construct.
         * @param string $value The option value.
         * @param string $text The option text.
         */
        public function __construct($value, $text)
        {
                parent::__construct("option");
                parent::setAttr("value", $value);
                parent::setText($text);
        }

        /**
         * Set this option object as selected.
         */
        public function setSelected()
        {
                parent::setAttr("selected", "selected");
        }

        /**
         * Set this option object as disabled.
         */
        public function setDisabled()
        {
                parent::setAttr("disabled", "disabled");
        }

}

/**
 * This class represent an input element (text, hidden, ...). In essential,
 * all child objects in a form inherits from this class.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
abstract class Input extends Component
{

        /**
         * Constructor.
         * @param string $type The input element type.
         * @param string $name The input element name (optional).
         * @param string $value The input element value (optional).
         */
        public function __construct($type, $name = null, $value = null)
        {
                parent::__construct("input");
                parent::setAttr("type", $type);
                if (isset($name)) {
                        parent::setAttr("name", $name);
                }
                if (isset($value)) {
                        parent::setAttr("value", $value);
                }
        }

        /**
         * Set the disable attribute.
         */
        public function setDisabled()
        {
                parent::setAttr("disabled", "disabled");
        }

}

/**
 * Represent an hidden field element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class HiddenField extends Input
{

        /**
         * Constructor.
         * @param string $name The field name.
         * @param string $value The field value.
         */
        public function __construct($name, $value)
        {
                parent::__construct("hidden", $name, $value);
        }

}

/**
 * Decorates an textbox and password class.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @see TextBox
 * @see Password
 */
abstract class TextInput extends Input
{

        /**
         * Sets the value attribute.
         * @param string $text The text.
         */
        public function setText($text)
        {
                parent::setAttr("value", $text);
        }

        /**
         * Sets the size attribute.
         * @param int $size
         */
        public function setSize($size)
        {
                parent::setAttr("size", $size);
        }

        /**
         * Sets the readonly attribute.
         */
        public function setReadOnly()
        {
                parent::setAttr("readonly", "readonly");
        }

        /**
         * Sets the maxlength attribute.
         * @param int $chars
         */
        public function setMaxLength($chars)
        {
                parent::setAttr("maxlength", $chars);
        }

}

/**
 * Decorates the radio button and checkbox class.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see RadioButton
 * @see CheckBox
 */
abstract class OptionInput extends Input
{

        private $text;

        /**
         * Constructor.
         * @param string $type The input element type.
         * @param string $name The input element name.
         * @param string $value The input element value.
         * @param string $text The text associated with this element (e.g. label).
         */
        public function __construct($type, $name, $value, $text = null)
        {
                parent::__construct($type, $name, $value);
                $this->text = isset($text) ? $text : $value;
        }

        /**
         * Sets the text for this input element.
         * @param string $text
         */
        public function setText($text)
        {
                $this->text = $text;
        }

        public function setLink($link, $type = LINK_TYPE_HREF)
        {
                $this->text = new Cdata($this->text);
                $this->text->setLink(new Link($type, $link));
        }

        /**
         * Sets the checked attribute.
         */
        public function setChecked()
        {
                parent::setAttr("checked", "checked");
        }

        /**
         * Output HTM for this object.
         */
        public function output()
        {
                parent::output();
                if (is_string($this->text)) {
                        echo $this->text;
                } else {
                        $this->text->output();
                }
        }

}

/**
 * This class represents a textbox. Its intended use is inside a form.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class TextBox extends TextInput
{

        /**
         * Constructor.
         * @param string $name The input element name.
         * @param string $text The text to display.
         */
        public function __construct($name, $text = null)
        {
                parent::__construct("text", $name, $text);
        }

}

/**
 * This class represents a password input textbox.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class Password extends TextInput
{

        /**
         * Constructor.
         * @param string $name The input element name.
         * @param string $text The textbox text.
         */
        public function __construct($name, $text = null)
        {
                parent::__construct("password", $name, $text);
        }

}

/**
 * This class represents an radio button.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class RadioButton extends OptionInput
{

        /**
         * Constructor.
         * @param string $name The input element name.
         * @param string $value The input element value.
         * @param string $text The text label.
         */
        public function __construct($name, $value, $text = null)
        {
                parent::__construct("radio", $name, $value, $text);
        }

}

/**
 * This class represents an checkbox.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class CheckBox extends OptionInput
{

        /**
         * Constructor.
         * @param string $name The input element name.
         * @param string $value The input element value.
         * @param string $text The text label.
         */
        public function __construct($name, $value, $text = null)
        {
                parent::__construct("checkbox", $name, $value, $text);
        }

}

/**
 * This class represents an file upload field in a form object.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class FileInput extends Input
{

        /**
         * Constructor.
         * @param string $name The file upload name.
         */
        public function __construct($name)
        {
                parent::__construct("file", $name);
        }

        /**
         * A comma separated list of accepted MIME types.
         *
         * @param string $mime Accepted MIME types (advisory only).
         */
        public function setAccept($mime)
        {
                parent::setAttr("accept", $mime);
        }

}

/**
 * An submit button for an form object.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class SubmitButton extends Input
{

        /**
         * Constructor.
         * @param string $name The button name.
         * @param string $label The button label.
         */
        public function __construct($name = null, $label = null)
        {
                parent::__construct("submit", $name, $label);
        }

}

/**
 * An reset button for an form object.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class ResetButton extends Input
{

        /**
         * Constructor.
         * @param string $name The reset button name.
         * @param string $label The button label. 
         */
        public function __construct($name = null, $label = null)
        {
                parent::__construct("reset", $name, $label);
        }

}

/**
 * An standard button for use in a form. This class maps to the button element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class StandardButton extends Input
{

        public function __construct($name = null, $label = null)
        {
                parent::__construct("button", $name, $label);
        }

}

/**
 * An image button is an ordinary img element, but with an form post trigger.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class ImageButton extends Input
{

        /**
         * Constructor.
         * @param string $name The button element name.
         * @param string $value The button element value.
         * @param string $src The URI of the image data.
         * @param string $alt An alternative text to display.
         */
        public function __construct($name = null, $value = null, $src = null, $alt = null)
        {
                parent::__construct("image", $name, $value);
                if (isset($src)) {
                        parent::setAttr("src", $src);
                }
                if (isset($alt)) {
                        parent::setAttr("alt", $alt);
                }
        }

        /**
         * Sets the src attribute.
         * @param string $src The URI.
         */
        public function setSource($src)
        {
                parent::setAttr("src", $src);
        }

        /**
         * Sets the alt attribute.
         * @param string $text An alternative text.
         */
        public function setAltText($text)
        {
                parent::setAttr("alt", $text);
        }

}

/**
 * A textarea container. The textarea is a bit special in that it is actually
 * a container, but is commonly used as a component in forms.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see Form
 */
class TextArea extends Container
{

        /**
         * Constructor.
         * @param string $name The textarea name.
         * @param string $text The textarea content.
         */
        public function __construct($name, $text = null)
        {
                parent::__construct("textarea");
                parent::setAttr("name", $name);
                parent::setText($text);
        }

        /**
         * Sets the cols attribute, that is, the number of columns to display
         * at once.
         *
         * @param int $cols
         */
        public function setColumns($cols)
        {
                parent::setAttr("cols", $cols);
        }

        /**
         * Sets the rows attribute, that is, the number of rows to display
         * at once.
         *
         * @param int $rows
         */
        public function setRows($rows)
        {
                parent::setAttr("rows", $rows);
        }

}

/**
 * This class represent a HTML form.
 *
 * Convenience function is provided for commonly used form elements, for others,
 * use the addElement() function from the Container class.
 *
 * <code>
 * <?php
 *
 * $options = array('opt1' => 'val1', 'opt2' => 'val2');
 * $form = new Form('script.php');
 * $form->addHidden('arg1', $val);
 * $combo = $form->addComboBox('opt');
 * foreach($options as $name => $value) {
 *     $combo->addOption($value, $name);
 * }
 * $form->addSubmitButton();
 * $form->output();
 *
 * ?>
 * </code>
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Form extends Container
{

        /**
         * Constructor.
         *
         * The default action method can be overridden by defining
         * FORM_ACTION_DEFAULT before including this script.
         *
         * @param string $action The action attribute.
         * @param string $method The method attribute.
         */
        public function __construct($action, $method = FORM_ACTION_DEFAULT)
        {
                parent::__construct("form");
                parent::setAttr("action", $action);
                parent::setAttr("method", $method);
        }

        /**
         * Sets the name attribute of this form.
         * @param string $name
         */
        public function setName($name)
        {
                parent::setAttr("name", $name);
        }

        /**
         * Sets the encoding attribute.
         * @param string $mime
         */
        public function setEncodingType($mime)
        {
                parent::setAttr("enctype", $mime);
        }

        /**
         * Set the accept attribute. The value is a comma separated list of
         * accepted MIME types.
         *
         * @param string $mime
         */
        public function setAccept($mime)
        {
                parent::setAttr("accept", $mime);
        }

        /**
         * Sets the accept-charset attribute. The value is a comma and/or
         * space separated list of accepted charset values.
         *
         * @param string $charset
         */
        public function setAcceptCharset($charset)
        {
                parent::setAttr("accept-charset", $charset);
        }

        /**
         * Adds an hidden field to this form. Returns the hidden field object.
         * @param string $name The hidden field name.
         * @param string $value The hidden field value.
         * @return HiddenField
         */
        public function addHidden($name, $value)
        {
                return parent::addElement(new HiddenField($name, $value));
        }

        /**
         * Adds an textbox to this form. Returns the textbox object.
         * @param string $name The textbox name.
         * @param string $text The textbox text.
         * @return TextBox
         */
        public function addTextBox($name, $text = null)
        {
                return parent::addElement(new TextBox($name, $text));
        }

        /**
         * Adds an password input to this form. Returns the password input object.
         * @param string $name The password input name.
         * @param string $text The password input text.
         * @return Password
         */
        public function addPassword($name, $text = null)
        {
                return parent::addElement(new Password($name, $text));
        }

        /**
         * Adds an combobox to this form. Returns the combobox object.
         * @param string $name The combobox name.
         * @return ComboBox
         */
        public function addComboBox($name)
        {
                return parent::addElement(new ComboBox($name));
        }

        /**
         * Adds an checkbox to this form. Returns the checkbox object.
         * @param string $name The checkbox name.
         * @param string $value The checkbox value.
         * @param string $text The optional buddy text for the checkbox (label).
         * @return CheckBox
         */
        public function addCheckBox($name, $value, $text = null)
        {
                return parent::addElement(new CheckBox($name, $value, $text));
        }

        /**
         * Adds an radio button to this form. Returns the radio button object.
         * @param string $name The button name.
         * @param string $value The button value.
         * @param string $text The optional buddy text for the radio button (label).
         * @return RadioButton
         */
        public function addRadioButton($name, $value, $text = null)
        {
                return parent::addElement(new RadioButton($name, $value, $text));
        }

        /**
         * Adds an textarea to this form. Returns the textarea object.
         * @param string $name The textarea name.
         * @param string $text The textarea content.
         * @return TextArea
         */
        public function addTextArea($name, $text = null)
        {
                return parent::addElement(new TextArea($name, $text));
        }

        /**
         * Adds an file upload field to this form. Returns the file input object.
         * @param string $name The file upload name.
         * @return FileInput
         */
        public function addFileInput($name)
        {
                return parent::addElement(new FileInput($name));
        }

        /**
         * Adds an submit button to this form. Returns the submit button object.
         * @param string $name The submit button name (optional).
         * @param string $label The button label (optional).
         * @return SubmitButton
         */
        public function addSubmitButton($name = "submit", $label = null)
        {
                return parent::addElement(new SubmitButton($name, $label));
        }

        /**
         * Adds an reset button to this form. Returns the reset button object.
         * @param string $name The reset button name (optional).
         * @param string $label The button label (optional).
         * @return ResetButton
         */
        public function addResetButton($name = "reset", $label = null)
        {
                return parent::addElement(new ResetButton($name, $label));
        }

        /**
         * Adds an standard button to this form. Returns the button object.
         * @param string $name The button name.
         * @param string $label The button label (optional).
         * @return StandardButton
         */
        public function addStandardButton($name, $label = null)
        {
                return parent::addElement(new StandardButton($name, $label));
        }

        /**
         * Alternative function for adding a button to this form. Returns the
         * button object.
         * @param string $type One of the BUTTON_XXX constants.
         * @param string $label The button label.
         * @return SubmitButton|ResetButton|StandardButton
         */
        public function addButton($type = BUTTON_SUBMIT, $label = null)
        {
                switch ($type) {
                        case BUTTON_SUBMIT:
                                return parent::addElement(new SubmitButton($type, $label));
                        case BUTTON_RESET:
                                return parent::addElement(new ResetButton($type, $label));
                        case BUTTON_STANDARD:
                                return parent::addElement(new StandardButton($type, $label));
                }
        }

        /**
         * Adds an empty line to this form.
         * @return BR
         */
        public function addSpace()
        {
                return parent::addElement(new BR());
        }

        /**
         * Adds an hrozontal row (HR) to this form.
         * @return HR
         */
        public function addLine()
        {
                return parent::addElement(new HR());
        }

        /**
         * Adds an section header to this form. This is useful for dividing
         * a big form into multiple sections. The section header is using CSS
         * style secthead by default.
         *
         * @param string $text The section header text.
         * @param string $class The CSS class.
         * @return H4
         */
        public function addSectionHeader($text, $class = null)
        {
                $header = parent::addElement(new H4($text));
                $header->setClass(isset($class) ? $class : "secthead");
                return $header;
        }

}

/**
 * HTML table class.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Table extends Container
{

        /**
         * Constructor.
         */
        public function __construct()
        {
                parent::__construct("table");
        }

        /**
         * Set border attribute.
         * @param int $width
         */
        public function setBorder($width)
        {
                parent::setAttr("border", $width);
        }

        /**
         * Set cell padding attribute.
         * @param int $pixels
         */
        public function setCellPadding($pixels)
        {
                parent::setAttr("cellpadding", $pixels);
        }

        /**
         * Set cell spacing attribute
         * @param int $pixels
         */
        public function setCellSpacing($pixels)
        {
                parent::setAttr("cellspacing", $pixels);
        }

        /**
         * See http://www.w3schools.com/tags/tag_table.asp for argument info
         * and the constants TABLE_FRAME_XXX.
         */
        public function setFrame($str)
        {
                parent::setAttr("frame", $str);
        }

        /**
         * See http://www.w3schools.com/tags/tag_table.asp for argument info.
         * and the constants TABLE_RULES_XXX.
         */
        public function setRules($str)
        {
                parent::setAttr("rules", $str);
        }

        /**
         * Set table summary attribute.
         * @param string $text
         */
        public function setSummary($text)
        {
                parent::setAttr("summary", $text);
        }

        /**
         * Add an row to the HTML table object.
         * @return TableRow
         */
        public function addRow()
        {
                return parent::addElement(new TableRow());
        }

        /**
         * Add an table header object.
         * @return THead
         */
        public function addTHead()
        {
                return parent::addElement(new THead());
        }

        /**
         * Add an table footer object.
         * @return TFoot
         */
        public function addTFoot()
        {
                return parent::addElement(new TFoot());
        }

        /**
         * Add an table body object.
         * @return TBody
         */
        public function addTBody()
        {
                return parent::addElement(new TBody());
        }

}

/**
 * The abstract base class for TableCell and TableRow classes.
 *
 * @see TableCell
 * @see TableRow
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class TableItem extends Container
{

        /**
         * Set the horizontal alignment attribute. See TABLE_ALIGN_XXX.
         * @param string $value
         */
        public function setAlign($value)
        {
                parent::setAttr("align", $value);
        }

        /**
         * Set the vertical alignment attribute. See TABLE_VALIGN_XXX.
         * @param string $value
         */
        public function setValign($value)
        {
                parent::setAttr("valign", $value);
        }

        /**
         * Aligns the content in a cell to a character.
         * @param string $char A single character
         */
        public function setChar($char)
        {
                parent::setAttr("char", $char);
        }

        /**
         * Sets the number of characters the content will be aligned from the
         * character specified by the char attribute.
         *
         * @param int $num
         */
        public function setCharOffset($num)
        {
                parent::setAttr("charoff", $num);
        }

}

/**
 * The table row class represents a single table containing table header or
 * data elements.
 *
 * @see TableData
 * @see TableHeader
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TableRow extends TableItem
{

        /**
         * Constructor.
         */
        public function __construct()
        {
                parent::__construct("tr");
        }

        /**
         * Add an table header to this table row.
         *
         * @param string $text The header text.
         * @return TableHeader
         */
        public function addHeader($text)
        {
                return parent::addElement(new TableHeader($text));
        }

        /**
         * Add an table data to this table row.
         * @param string $text The cell text.
         * @return TableData
         */
        public function addData($text = "&nbsp;")
        {
                return parent::addElement(new TableData($text));
        }

}

/**
 * The abstract base class for TableHeader or TableData class.
 *
 * @see TableHeader
 * @see TableData
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class TableCell extends TableItem
{

        /**
         * Sets a link for this table cell. The link type is either of href
         * or an name (anchor) type. Returns the link object.
         *
         * @param string $str The resource target.
         * @param string $type The link type
         * @return Link
         */
        public function setLink($str, $type = LINK_TYPE_HREF)
        {
                return parent::setLink(new Link($type, $str));
        }

        /**
         * Set the abbr attribute.
         * @param string $text
         */
        public function setAbbr($text)
        {
                parent::setAttr("abbr", $text);
        }

        /**
         * Set the axis attribute.
         * @param string $category
         */
        public function setAxis($category)
        {
                parent::setAttr("axis", $category);
        }

        /**
         * Set the column span attribute.
         * @param int $num
         */
        public function setColspan($num)
        {
                parent::setAttr("colspan", $num);
        }

        /**
         * Set the row span attribute.
         * @param int $num
         */
        public function setRowspan($num)
        {
                parent::setAttr("rowspan", $num);
        }

        /**
         * Set the no wrap attribute.
         * @deprecated This property is deprecated in favor of CSS.
         */
        public function setNoWrap()    // Deprecated
        {
                parent::setAttr("nowrap", "nowrap");
        }

        /**
         * Set the scope attribute. See TABLE_ITEM_SCOPE_XXX.
         * @param string $scope
         */
        public function setScope($scope)
        {
                parent::setAttr("scope", $scope);
        }

        /**
         * This is a convenience function for:
         *
         * <code>
         * <?php
         * $cell->setLink($name, LINK_TYPE_NAME)->setTitle($str);
         * ?>
         * </code>
         *
         * @param string $str The resource target.
         * @param string $title Tooltip
         */
        public function setAnchor($str, $title)
        {
                parent::setLink(new Link(LINK_TYPE_NAME, $str))->setTitle($title);
        }

}

/**
 * This class represents a table header (TH) element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TableHeader extends TableCell
{

        public function __construct($text)
        {
                parent::__construct("th");
                parent::setText($text);
        }

}

/**
 * This class represents a table data (TD) element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TableData extends TableCell
{

        /**
         * Constructor.
         * @param string $text The cell text.
         */
        public function __construct($text)
        {
                parent::__construct("td");
                parent::setText($text);
        }

        /**
         * Set the headers attribute. Specifies the table headers related to a cell.
         * @param string $hid
         */
        public function setHeaders($hid)
        {
                parent::setAttr("headers", $hid);
        }

        /**
         * Adds an textbox to this table cell. This is an convenience method.
         * @param string $name The textbox name.
         * @param string $text The textbox text.
         * @return TextBox 
         */
        public function addTextBox($name, $text = null)  // A convenient function
        {
                return parent::addElement(new TextBox($name, $text));
        }

}

/**
 * The abstract base class for TBody, TFoot and THead
 *
 * @see TBody
 * @see TFoot
 * @see THead
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class TContainer extends Container
{

        /**
         * Adds an table row to the parent container.
         * @return TableRow
         */
        public function addRow()
        {
                return parent::addElement(new TableRow());
        }

}

/**
 * Represent an thead element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class THead extends TContainer
{

        public function __construct()
        {
                parent::__construct("thead");
        }

}

/**
 * Represent an tfoot element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TFoot extends TContainer
{

        public function __construct()
        {
                parent::__construct("tfoot");
        }

}

/**
 * Represent an tbody element.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TBody extends TContainer
{

        /**
         * Constructor.
         */
        public function __construct()
        {
                parent::__construct("tbody");
        }

        /**
         * Set the horizontal align attribute.
         * @param string $value
         * @see TableItem::setAlign
         */
        public function setAlign($value)
        {
                parent::setAttr("align", $value);
        }

        /**
         * Set the vertical align attribute.
         * @param string $value
         * @see TableItem::setValign
         */
        public function setValign($value)
        {
                parent::setAttr("valign", $value);
        }

        /**
         * Set the char attribute.
         * @param string $char A single character.
         * @see TableItem::setChar
         */
        public function setChar($char)
        {
                parent::setAttr("char", $char);
        }

        /**
         * Set the charoff attribute.
         * @param <type> $num
         * @see TableItem::setCharOffset
         */
        public function setCharOffset($num)
        {
                parent::setAttr("charoff", $num);
        }

}

/**
 * Generic HTML container. This is a convenience class.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Content
{

        private $content = array();

        /**
         * Add content object to this object.
         *
         * @param CData|Element $content
         * @return CData|Element
         */
        public function addContent($content)
        {
                $this->content[] = $content;
                return $content;
        }

        /**
         * Adds an header to this object.
         *
         * @param string $text The header text.
         * @param int $size The header size.
         * @return Header
         */
        public function addHeader($text, $size = 3)
        {
                return $this->addContent(new Header($size, $text));
        }

        /**
         * Add a section of plain text.
         * @param string|array $text The text.
         * @return Cdata
         */
        public function addText($text)
        {
                return $this->addContent(new Cdata($text));
        }

        /**
         * Add an paragraph object. The text argument might contain an array
         * of string that's going to be concatenated.
         * @param string|array $text The paragraph content.
         * @return Paragraph
         */
        public function addParagraph($text)
        {
                return $this->addContent(new Paragraph($text));
        }

        /**
         * Add an table object.
         * @return Table
         */
        public function addTable()
        {
                return $this->addContent(new Table());
        }

        /**
         * Add an form object. See the documentation of class Form for more
         * information.
         * 
         * @param string $action The action attribute.
         * @param string $method The method attribute.
         * @return Form
         */
        public function addForm($action, $method = FORM_ACTION_DEFAULT)
        {
                return $this->addContent(new Form($action, $method));
        }

        public function output()
        {
                foreach ($this->content as $content) {
                        $content->output();
                }
        }

}

/**
 * MessageBox class.
 *
 * This class defines a messagebox providing an standardized look-and-feel
 * for the displayed message. The messagebox is either used to display hinting,
 * success, information, warnings or error messages.
 *
 * The hint type is an extension to the standard messagebox types, and is
 * intended for display of user hints (like short usage tips).
 *
 * These three different use cases will all display the same messagebox:
 *
 * <code>
 * <?php
 * $title = "Greeting";
 * $text = "Hello world!";
 * $icon = "icons/custom/info.png";
 *
 * // 
 * // Using get/set methods:
 * // 
 * $messagebox = new MessageBox();
 * $messagebox->setIcon($icon);
 * $messagebox->setTitle($title);
 * $messagebox->setMessage($text);
 * $messagebox->display();
 *
 * // 
 * // Pass properties to constructor:
 * // 
 * $messagebox = new MessageBox(
 *     MessageBox::information,
 *     $text, $title, $icon);
 * $messagebox->display();
 *
 * // 
 * // Use static method:
 * // 
 * MessageBox::show(MessageBox::information, $text, $title, $icon);
 * ?>
 * </code>
 *
 * The HTML generated by code above will be:
 *
 * <code>
 * <div class="mbox info">
 *   <div class="mbox-head"><img src="icons/custom/info.png" alt=""/>Greeting</div>
 *   <div class="mbox-text">Hello world!</div>
 * </div>
 * </code>
 *
 * By default, the messagebox will be of information type. The type is either
 * set at construction time or later by calling setClass() on the messagebox
 * object.
 *
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class MessageBox extends Container
{

        const hint = "hint";
        const success = "success";
        const information = "info";
        const warning = "warn";
        const error = "error";

        private $icon;
        private $head;
        private $text;

        /**
         * Constructor.
         * @param string $type Type of messagebox.
         * @param string $text The message string.
         * @param string|bool $title The title text or true to use default title. Pass null to exclude title message.
         * @param string|Image $icon The messagebox icon. Pass null to use default icon.
         */
        public function __construct($type = self::information, $text = "", $title = true, $icon = null)
        {
                parent::__construct("div");
                parent::setClass(sprintf("mbox %s", $type));

                if (!isset($icon)) {                    // deduce icon from type
                        $icon = sprintf("%s/%s.png", MESSAGEBOX_ICON_URL, $type);
                }
                if (is_bool($title) && $title) {        // deduce title from type
                        $title = constant(sprintf("MESSAGEBOX_TITLE_%s", strtoupper($type)));
                }

                $this->setTitle($title);
                $this->setIcon($icon);
                $this->setMessage($text);

                parent::addElement($this);              // add output hook
        }

        /**
         * Factory function.
         * 
         * This is a convenient function that creates a messagebox object and
         * calls display() on it.
         * 
         * @param string $type Type of messagebox.
         * @param string $text The message string.
         * @param string|bool $title The title text or true to use default title. Pass null to exclude title message.
         * @param string|Image $icon The messagebox icon. Pass null to use default icon.
         */
        public static function show($type = self::information, $text = "", $title = true, $icon = null)
        {
                $messagebox = new MessageBox($type, $text, $title, $icon);
                $messagebox->display();
        }

        /**
         * Set the messagebox icon.
         * @param string|Image $icon The messagebox icon.
         */
        public function setIcon($icon)
        {
                if (is_string($icon)) {
                        $this->icon = new Image($icon, "");
                } else {
                        $this->icon = $icon;
                }
        }

        /**
         * Set the messagebox title.
         * @param string $title The title text or null to exclude title message.
         */
        public function setTitle($text)
        {
                $this->head = $text;
        }

        /**
         * Set the message string.
         * @param string $text The message string.
         */
        public function setMessage($text)
        {
                $this->text = $text;
        }

        /**
         * Output the messagebox.
         *
         * This function should not be called direct. Instead, it is solely for
         * use by the parent class.
         */
        public function output()
        {
                if (is_string($this->head)) {
                        $this->head = new Cdata($this->head);
                }
                if (is_string($this->text)) {
                        $this->text = new Cdata($this->text);
                }
                if (isset($this->head)) {
                        $content = new Content();
                        $div = $content->addContent(new Container("div"));
                        $div->setClass("mbox-head");
                        $div->addElement($this->icon);
                        $div->addElement($this->head);
                        $div = $content->addContent(new Container("div"));
                        $div->setClass("mbox-text");
                        $div->addElement($this->text);
                        $content->output();
                } else {
                        $content = new Content();
                        $div = $content->addContent(new Container("div"));
                        $div->setClass("mbox-text");
                        $div->addElement($this->icon);
                        $div->addElement($this->text);
                        $content->output();
                }
        }

        /**
         * Call this function to display the messagebox.
         */
        public function display()
        {
                parent::output();
        }

}

/**
 * This class represents a div container (DIV) element.
 * 
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Div extends Container
{

        public function __construct()
        {
                parent::__construct("div");
        }

}

/**
 * This class represents a span container (SPAN) element.
 * 
 * @package UserInterface
 * @subpackage HTML
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Span extends Container
{

        public function __construct()
        {
                parent::__construct("span");
        }

}

?>
