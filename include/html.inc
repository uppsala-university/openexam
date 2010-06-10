<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/html.inc
// Author: Anders Lövgren
// Date:   2010-05-30
// 
// Common HTML classes. These classes should give more structured code by
// providing a object oriented approach to i.e. form and tables.
// 
// The Component class represent some kind of non-containing tag, for
// example, it could be a input tag. The Container class can contain other
// containers or components.
// 
// Notes:
// 
// 1. Perhaps should we return the cdata object from the container class, so
//    that this code is possible:
// 
//    $data = $row->addData($text);
//    $link = $data->getText()->addLink($url);
// 
//      // or maybe:
// 
//    $data = $row->addData($text);
//    $data->addLink($data->getText(), $url);
// 
//    However, this opens up for invalid (or at least unwanted) code like:
// 
//    $area = new TextArea(...);
//    $area->getText()->addLink($url);   // This is no good
//
// 2. Disclaimer: Not all HTML tags are implemented, and those that are might
///   have missing attributes. This is intentional, as the goal was to provide 
//    an object oriented framework for the most commonly used HTML tags in a 
//    page body.
// 

// 
// The javascript events. Note that not all events are supported by all HTML
// tags. See http://www.w3schools.com/tags/ref_eventattributes.asp.
// 
// Using defined contants instead of string helps to detect misspelled event 
// attributes that would be silently ignored by browers.
// 
// Body and frameset events:
// 
if(!defined('EVENT_ON_LOAD')) {
    define ('EVENT_ON_LOAD', "onload");              // Script to be run when a document load
}
if(!defined('EVENT_ON_UNLOAD')) {
    define ('EVENT_ON_UNLOAD', "onunload");          // Script to be run when a document unload
}
// 
// Form events:
// 
if(!defined('EVENT_ON_BLUR')) {
    define ('EVENT_ON_BLUR', "onblur");              // Script to be run when an element loses focus
}
if(!defined('EVENT_ON_CHANGE')) {
    define ('EVENT_ON_CHANGE', "onchange");          // Script to be run when an element change
}
if(!defined('EVENT_ON_FOCUS')) {
    define ('EVENT_ON_FOCUS', "onfocus");            // Script to be run when an element gets focus
}
if(!defined('EVENT_ON_RESET')) {
    define ('EVENT_ON_RESET', "onreset");            // Script to be run when a form is reset
}
if(!defined('EVENT_ON_SELECT')) {
    define ('EVENT_ON_SELECT', "onselect");          // Script to be run when an element is selected
}
if(!defined('EVENT_ON_SUBMIT')) {
    define ('EVENT_ON_SUBMIT', "onsubmit");          // Script to be run when a form is submitted
}
// 
// Image events:
// 
if(!defined('EVENT_ON_ABORT')) {
    define ('EVENT_ON_ABORT', "onabort");            // Script to be run when a form is submitted
}
// 
// Keyboard events:
// 
if(!defined('EVENT_ON_KEY_DOWN')) {
    define ('EVENT_ON_KEY_DOWN', "keydown");         // Script to be run when a key is pressed
}
if(!defined('EVENT_ON_KEY_PRESS')) {
    define ('EVENT_ON_KEY_PRESS', "keypress");       // Script to be run when a key is pressed and released
}
if(!defined('EVENT_ON_KEY_UP')) {
    define ('EVENT_ON_KEY_UP', "keyup");             // Script to be run when a key is released
}
// 
// Mouse events:
// 
if(!defined('EVENT_ON_CLICK')) {
    define ('EVENT_ON_CLICK', "onclick");            // Script to be run on a mouse click
}
if(!defined('EVENT_ON_DBL_CLICK')) {
    define ('EVENT_ON_DBL_CLICK', "ondblclick");     // Script to be run on a mouse double-click
}
if(!defined('EVENT_ON_DOUBLE_CLICK')) {
    define ('EVENT_ON_DOUBLE_CLICK', "ondblclick");  // Script to be run on a mouse double-click
}
if(!defined('EVENT_ON_MOUSE_DOWN')) {
    define ('EVENT_ON_MOUSE_DOWN', "onmousedown");   // Script to be run when mouse button is pressed
}
if(!defined('EVENT_ON_MOUSE_MOVE')) {
    define ('EVENT_ON_MOUSE_MOVE', "onmousemove");   // Script to be run when mouse pointer moves
}
if(!defined('EVENT_ON_MOUSE_OUT')) {
    define ('EVENT_ON_MOUSE_OUT', "onmouseout");     // Script to be run when mouse pointer moves out of an element
}
if(!defined('EVENT_ON_MOUSE_OVER')) {
    define ('EVENT_ON_MOUSE_OVER', "onmouseover");   // Script to be run when mouse pointer moves over an element
}
if(!defined('EVENT_ON_MOUSE_UP')) {
    define ('EVENT_ON_MOUSE_UP', "onmouseup");       // Script to be run when mouse button is released
}

// 
// Table frame attribute constants:
// 
if(!defined('TABLE_FRAME_VOID')) {
    define ('TABLE_FRAME_VOID', "void");
}
if(!defined('TABLE_FRAME_ABOVE')) {
    define ('TABLE_FRAME_ABOVE', "above");
}
if(!defined('TABLE_FRAME_BELOW')) {
    define ('TABLE_FRAME_BELOW', "below");
}
if(!defined('TABLE_FRAME_HSIDES')) {
    define ('TABLE_FRAME_HSIDES', "hsides");
}
if(!defined('TABLE_FRAME_LHS')) {
    define ('TABLE_FRAME_LHS', "lhs");
}
if(!defined('TABLE_FRAME_RHS')) {
    define ('TABLE_FRAME_RHS', "rhs");
}
if(!defined('TABLE_FRAME_VSIDES')) {
    define ('TABLE_FRAME_VSIDES', "vsides");
}
if(!defined('TABLE_FRAME_BOX')) {
    define ('TABLE_FRAME_BOX', "box");
}
if(!defined('TABLE_FRAME_BORDER')) {
    define ('TABLE_FRAME_BORDER', "border");
}

// 
// Table rules attribute constants:
// 
if(!defined('TABLE_RULES_NONE')) {
    define ('TABLE_RULES_NONE', "none");
}
if(!defined('TABLE_RULES_GROUPS')) {
    define ('TABLE_RULES_GROUPS', "groups");
}
if(!defined('TABLE_RULES_ROWS')) {
    define ('TABLE_RULES_ROWS', "rows");
}
if(!defined('TABLE_RULES_COLS')) {
    define ('TABLE_RULES_COLS', "cols");
}
if(!defined('TABLE_RULES_ALL')) {
    define ('TABLE_RULES_ALL', "all");
}

// 
// Constants for TBody and TableItem (base class of TableRow, TableHeader 
// and TableData) align and valign attributes:
// 
if(!defined('TABLE_ALIGN_RIGHT')) {
    define ('TABLE_ALIGN_RIGHT', "right");
}
if(!defined('TABLE_ALIGN_LEFT')) {
    define ('TABLE_ALIGN_LEFT', "left");
}
if(!defined('TABLE_ALIGN_CENTER')) {
    define ('TABLE_ALIGN_CENTER', "center");
}
if(!defined('TABLE_ALIGN_JUSTIFY')) {
    define ('TABLE_ALIGN_JUSTIFY', "justify");
}
if(!defined('TABLE_ALIGN_CHAR')) {
    define ('TABLE_ALIGN_CHAR', "char");
}

if(!defined('TABLE_VALIGN_TOP')) {
    define ('TABLE_VALIGN_TOP', "top");
}
if(!defined('TABLE_VALIGN_MIDDLE')) {
    define ('TABLE_VALIGN_MIDDLE', "middle");
}
if(!defined('TABLE_VALIGN_BOTTOM')) {
    define ('TABLE_VALIGN_BOTTOM', "bottom");
}
if(!defined('TABLE_VALIGN_BASELINE')) {
    define ('TABLE_VALIGN_BASELINE', "baseline");
}

// 
// Constants for the scope attribute of TableCell:
// 
if(!defined('TABLE_SCOPE_COL')) {
    define ('TABLE_SCOPE_COL', "col");
}
if(!defined('TABLE_SCOPE_ROW')) {
    define ('TABLE_SCOPE_ROW', "row");
}
if(!defined('TABLE_SCOPE_COLGROUP')) {
    define ('TABLE_SCOPE_COLGROUP', "colgroup");
}
if(!defined('TABLE_SCOPE_ROWGROUP')) {
    define ('TABLE_SCOPE_ROWGROUP', "rowgroup");
}
  
// 
// Link types:
// 
if(!defined('LINK_TYPE_HREF')) {
    define ('LINK_TYPE_HREF', "href");
}
if(!defined('LINK_TYPE_NAME')) {
    define ('LINK_TYPE_NAME', "name");
}

// 
// Link attributes:
// 
if(!defined('LINK_SHAPE_DEFAULT')) {
    define ('LINK_SHAPE_DEFAULT', "default");
}
if(!defined('LINK_SHAPE_RECT')) {
    define ('LINK_SHAPE_RECT', "rect");
}
if(!defined('LINK_SHAPE_CIRCLE')) {
    define ('LINK_SHAPE_CIRCLE', "circle");
}
if(!defined('LINK_SHAPE_POLY')) {
    define ('LINK_SHAPE_POLY', "poly");
}

if(!defined('LINK_TARGET_BLANK')) {
    define ('LINK_TARGET_BLANK', "_blank");
}
if(!defined('LINK_TARGET_PARENT')) {
    define ('LINK_TARGET_PARENT', "_parent");
}
if(!defined('LINK_TARGET_SELF')) {
    define ('LINK_TARGET_SELF', "_self");
}
if(!defined('LINK_TARGET_TOP')) {
    define ('LINK_TARGET_TOP', "_top");
}

// 
// Constants for button types.
// 
if(!defined('BUTTON_SUBMIT')) {
    define ('BUTTON_SUBMIT', "submit");
}
if(!defined('BUTTON_RESET')) {
    define ('BUTTON_RESET', "reset");
}
if(!defined('BUTTON_STANDARD')) {
    define ('BUTTON_STANDARD', "standard");
}

// 
// Some handy inline event handlers:
// 
if(!defined('EVENT_HANDLER_CLEAR_CONTENT')) {
    define ('EVENT_HANDLER_CLEAR_CONTENT', "this.value=''");
}

// 
// Output mode for HTML element:
// 
if(!defined('ELEMENT_OUTPUT_COMPONENT')) {
    define ('ELEMENT_OUTPUT_COMPONENT', 1);
}
if(!defined('ELEMENT_OUTPUT_CONTAINER_START')) {
    define ('ELEMENT_OUTPUT_CONTAINER_START', 2);
}
if(!defined('ELEMENT_OUTPUT_CONTAINER_END')) {
    define ('ELEMENT_OUTPUT_CONTAINER_END', 3);
}

// 
// This class serves as the base class for component and container classes.
// 
class Element
{
    private $tag;                // The tag name (i.e. h2)
    private $class;              // The classname (CSS)
    private $id;                 // The unique ID (CSS)    
    private $style;              // Inline style
    private $title;              // The title attribute.
    private $event = array();    // Event attributes
    private $attr  = array();    // HTML tag attributes
    
    // 
    // Contruct the HTML tag element.
    // 
    protected function __construct($tag)
    {
	$this->tag = $tag;
    }
    
    // 
    // Set classname (CSS)
    // 
    public function setClass($class) 
    {
	$this->class = $class;
    }

    // 
    // Set unique ID (CSS)
    // 
    public function setId($id)
    {
	$this->class = $id;
    }
    
    // 
    // Set inline style.
    // 
    public function setStyle($style)
    {
	$this->style = $style;
    }
    
    // 
    // Set optional title (tooltip)
    // 
    public function setTitle($title)
    {
	$this->title = $title;
    }
    
    // 
    // Set javascript code for event.
    // 
    public function setEvent($event, $code)
    {
	$this->event[$event] = $code;
    }
    
    // 
    // Set an HTML attribute (i.e. name or selected).
    // 
    protected function setAttr($name, $value = null)
    {
	$this->attr[$name] = $value;
    }
    
    // 
    // Get the name of this element.
    // 
    public function getName()
    {
	return $this->attr['name'];
    }
    
    // 
    // Output this HTML element.
    // 
    protected function output($mode)
    {
	if($mode == ELEMENT_OUTPUT_CONTAINER_START ||
	   $mode == ELEMENT_OUTPUT_COMPONENT) {
	    printf("<%s", $this->tag);
	    if(isset($this->class)) {
		printf(" class=\"%s\"", $this->class);
	    }
	    if(isset($this->id)) {
		printf(" id=\"%s\"", $this->id);
	    }
	    if(isset($this->style)) {
		printf(" style=\"%s\"", $this->style);
	    }
	    if(count($this->attr) != 0) {
		foreach($this->attr as $name => $value) {
		    if(isset($name)) {
			if(isset($value)) {
			    printf(" %s=\"%s\"", $name, $value);
			} else {
			    printf(" %s", $name);
			}
		    }
		}
	    }
	    if(count($this->event) != 0) {
		foreach($this->event as $event => $code) {
		    printf(" %s=\"%s\"", $event, $code);
		}
	    }
	    if(isset($this->title)) {
		printf(" title=\"%s\"", $this->title);
	    }
	    
	    if($mode == ELEMENT_OUTPUT_COMPONENT) {
		printf("/>\n");
	    } else {
		printf(">");
	    }
	}
	if($mode == ELEMENT_OUTPUT_CONTAINER_END) {
	    printf("</%s>\n", $this->tag);
	}
    }
}

// 
// An href or name (anchor) link.
// 
class Link extends Container
{
    public function __construct($type, $link)
    {
	parent::__construct("a");
	parent::setAttr($type, $link);
    }
    
    public function setHref($url)
    {
	parent::setAttr("href", $url);
    }
    
    public function setName($name)
    {
	parent::setAttr("name", $name);
    }
    
    public function setCharSet($encoding)
    {
	parent::setAttr("charset", $encoding);
    }
    
    public function setCoordinates($coords)
    {
	parent::setAttr("coords", $coords);
    }
    
    public function setHrefLang($lang)
    {
	parent::setAttr("hreflang", $lang);
    }
    
    public function setRelation($text)
    {
	parent::setAttr("rel", $text);
    }
    
    public function setReverse($text)
    {
	parent::setAttr("rev", $text);
    }

    // 
    // Use one of the constants LINK_SHAPE_XXX.
    // 
    public function setShape($shape)
    {
	parent::setAttr("shape", $shape);
    }
    
    // 
    // Use one of the constants LINK_TARGET_XXX or a frame name.
    // 
    public function setTarget($target)
    {
	parent::setAttr("target", $target);
    }
}

// 
// This class represent a block of text.
// 
class Cdata
{
    private $text;
    private $link;
    
    public function __construct($text)
    {
	$this->text = $text;
    }
    
    public function setLink($link)
    {
	$this->link = $link;
	$this->link->addText(&$this->text);
    }
    
    public function output()
    {
	if(isset($this->link)) {
	    $this->link->output();
	} else {
	    echo $this->text;
	}
    }
    
    public function __toString()
    {
	return $this->text;
    }
}

class BR extends Cdata
{
    public function __construct()
    {
	parent::__construct("<br/>\n");
    }
}

class HR extends Cdata
{
    public function __construct()
    {
	parent::__construct("<hr/>\n");
    }
}

// 
// This class represent a non-containing HTML tag.
// 
class Component extends Element
{
    private $label;
    
    // 
    // Output this component:
    // 
    public function output()
    {
    	if(isset($this->label)) {
    	    $this->label->output();
    	}
    	parent::output(ELEMENT_OUTPUT_COMPONENT);
    }

    // 
    // Sets a label for this component. Calling this function makes sense 
    // only for visual elemements, like textboxes, not for hidden fields.
    // 
    public function setLabel($text = null)
    {
    	$this->label = new Label($this->getName(), $text);
    	return $this->label;
    }
}

// 
// This class represent a containing tag, like table, form or table row.
// 
class Container extends Element
{
    private $cdata;               // The character data object.
    private $label;               // Optional label.
    private $childs = array();    // Contained elements.
    
    // 
    // Add a child element (component, container or character data (cdata)).
    // 
    public function addElement($child)
    {
	$this->childs[] = $child;
	return $child;
    }
    
    // 
    // Add block of text (as cdata).
    // 
    public function addText($text)
    {
	$this->cdata = new Cdata($text);
	return $this->cdata;
    }

    // 
    // Sets a label for this component. Calling this function makes sense 
    // only for visual elemements, like textboxes, not for hidden fields.
    // 
    public function setLabel($text = null)
    {
    	$this->label = new Label($this->getName(), $text);
    	return $this->label;
    }
    
    // 
    // Set the link object.
    // 
    protected function setLink($link)
    {
	$this->cdata->setLink($link);
	return $link;
    }
    
    // 
    // Output this container and its child elements (if any).
    //
    public function output()
    {
	if(isset($this->label)) {
    	    $this->label->output();
	}
	parent::output(ELEMENT_OUTPUT_CONTAINER_START);
	if(isset($this->cdata)) {
	    $this->cdata->output();
	}
	if(count($this->childs) > 1) {
	    printf("\n");
	}
	foreach($this->childs as $child) {
	    $child->output();
	}
	parent::output(ELEMENT_OUTPUT_CONTAINER_END);
    }
}

// 
// This class represent a label for an element (i.e. a textbox).
// 
class Label extends Container
{
    public function __construct($for, $text = null)
    {
	parent::__construct("label");
	parent::setAttr("for", $for);
	parent::addText(isset($text) ? $text . ":" : "&nbsp;");
    }
    
    public function output()
    {
	printf("<br/>\n");
	parent::output();
    }
}

// 
// The standard H1 to H6 header, implemented as text container.
// 
class Header extends Container
{
    public function __construct($size, $text)
    {
	parent::__construct(sprintf("h%d", $size));
	parent::addText($text);
    }
}
class H1 extends Header
{
    public function __construct($text) { parent::__construct(1, $text); }
}
class H2 extends Header
{
    public function __construct($text) { parent::__construct(2, $text); }
}
class H3 extends Header
{
    public function __construct($text) { parent::__construct(3, $text); }
}
class H4 extends Header
{
    public function __construct($text) { parent::__construct(4, $text); }
}
class H5 extends Header
{
    public function __construct($text) { parent::__construct(5, $text); }
}
class H6 extends Header
{
    public function __construct($text) { parent::__construct(6, $text); }
}

// 
// A paragraph
// 
class Paragraph extends Container
{
    public function __construct($text = null)
    {
	parent::__construct("p");
	parent::addText($text);
    }
}

// 
// An image tag
// 
class Image extends Component
{
    public function __construct($src, $alt)
    {
	parent::__construct("img");
	parent::setAttr("src", $src);
	parent::setAttr("alt", $alt);
    }
    
    public function setHeight($pixels)
    {
	parent::setAttr("height", $pixels);
    }

    public function setWidth($pixels)
    {
	parent::setAttr("width", $pixels);
    }
    
    public function setLongDescription($url)
    {
	parent::setAttr("longdesc", $url);
    }
    
    public function setUseMap($name)
    {
	parent::setAttr("usemap", $name);
    }
}

// 
// A combobox (drop down list).
// 
class ComboBox extends Container
{
    public function __construct($name)
    {
	parent::__construct("select");
	parent::setAttr("name", $name);
    }
    
    public function setDisabled()
    {
	parent::setAttr("disabled", "disabled");	
    }

    public function setMultiple()
    {
	parent::setAttr("multiple", "multiple");	
    }
    
    public function setVisibleOptions($number)
    {
	parent::setAttr("size", $number);
    }
    
    public function addOption($value, $text)
    {
	return parent::addElement(new Option($value, $text));
    }
}

// 
// An option for the combobox
// 
class Option extends Container
{
    public function __construct($value, $text)
    {
	parent::__construct("option");
	parent::setAttr("value", $value);
	parent::addText($text);
    }
    
    public function setSelected()
    {
	parent::setAttr("selected", "selected");
    }

    public function setDisabled()
    {
	parent::setAttr("disabled", "disabled");	
    }
}

// 
// This class represent an input element (text, hidden, ...).
// 
abstract class Input extends Component
{
    public function __construct($type, $name = null, $value = null)
    {
	parent::__construct("input");
	parent::setAttr("type", $type);
	if(isset($name)) {
	    parent::setAttr("name", $name);
	}
	if(isset($value)) {
	    parent::setAttr("value", $value);
	}
    }
    
    public function setDisabled()
    {
	parent::setAttr("disabled", "disabled");	
    }
}

class HiddenField extends Input
{
    public function __construct($name, $value)
    {
	parent::__construct("hidden", $name, $value);
    }
}

// 
// Decorates textbox and password.
// 
abstract class TextInput extends Input
{
    public function setText($text)
    {
	parent::setAttr("value", $text);
    }
        
    public function setSize($size)
    {
	parent::setAttr("size", $size);
    }
    
    public function setReadOnly()
    {
	parent::setAttr("readonly", "readonly");
    }
    
    public function setMaxLength($chars)
    {
	parent::setAttr("maxlength", $chars);
    }
}

// 
// Decorates radio button and checkbox.
// 
abstract class OptionInput extends Input
{
    private $text;

    public function __construct($type, $name, $value, $text = null)
    {
	parent::__construct($type, $name, $value);
	$this->text = isset($text) ? $text : $value;
    }
    
    public function setText($text)
    {
	$this->text = $text;
    }
    
    public function setChecked()
    {
	parent::setAttr("checked", "checked");
    }
    
    public function output()
    {
	parent::output();
	echo $this->text;
    }
}

class TextBox extends TextInput
{
    public function __construct($name, $text = null)
    {
	parent::__construct("text", $name, $text);
    }    
}

class Password extends TextInput
{
    public function __construct($name, $text = null)
    {
	parent::__construct("password", $name, $text);
    }    
}

class RadioButton extends OptionInput
{
    public function __construct($name, $value, $text = null)
    {
	parent::__construct("radio", $name, $value, $text);
    }
}

class CheckBox extends OptionInput
{
    public function __construct($name, $label, $text = null)
    {
	parent::__construct("checkbox", $name, $label, $text);
    }
}

class FileInput extends Input
{
    public function __construct($name)
    {
	parent::__construct("file", $name);
    }
    
    // 
    // A comma separated list of accepted MIME types.
    // 
    public function setAccept($mime)
    {
	parent::setAttr("accept", $mime);
    }
}

class SubmitButton extends Input
{
    public function __construct($name = null, $label = null) 
    {
	parent::__construct("submit", $name, $label);
    }
}

class ResetButton extends Input
{
    public function __construct($name = null, $label = null) 
    {
	parent::__construct("reset", $name, $label);
    }
}

class StandardButton extends Input
{
    public function __construct($name = null, $label = null) 
    {
	parent::__construct("button", $name, $label);
    }
}

class ImageButton extends Input
{
    public function __construct($name = null, $value = null, $src = null, $alt = null)
    {
	parent::__construct("image", $name, $value);
	if(isset($src)) {
	    parent::setAttr("src", $src);
	}
	if(isset($alt)) {
	    parent::setAttr("alt", $alt);
	}
    }
    
    public function setSource($src)
    {
	parent::setAttr("src", $src);
    }
    
    public function setAltText($text)
    {
	parent::setAttr("alt", $alt);
    }
}

// 
// A textarea container. The textarea is a bit special in that it is actually 
// a container, but is commonly used as a component in forms.
// 
class TextArea extends Container
{
    public function __construct($name, $text = null)
    {
	parent::__construct("textarea");
	parent::setAttr("name", $name);
	parent::addText($text);
    }
    
    public function setColumns($cols)
    {
	parent::setAttr("cols", $cols);
    }
    
    public function setRows($rows)
    {
	parent::setAttr("rows", $rows);
    }
}

// 
// This class represent a HTML form.
// 
class Form extends Container
{
    // 
    // Constructs the form:
    // 
    public function __construct($action, $method = "GET")
    {
	parent::__construct("form");
	parent::setAttr("action", $action);
	parent::setAttr("method", $method);
    }
    
    public function setName($name)
    {
	parent::setAttr("name", $name);
    }

    public function setEncodingType($mime) 
    {
	parent::setAttr("enctype", $mime);
    }
    
    // 
    // A comma separated list of accepted MIME types.
    // 
    public function setAccept($mime)
    {
	parent::setAttr("accept", $mime);
    }
    
    // 
    // A comma and/or space separated list of accepted charset values.
    // 
    public function setAcceptCharset($charset)
    {
	parent::setAttr("accept-charset", $charset);
    }
    
    public function addHidden($name, $value)
    {
	return parent::addElement(new HiddenField($name, $value));
    }
    
    public function addTextBox($name, $text = null)
    {
	return parent::addElement(new TextBox($name, $text));
    }
    
    public function addComboBox($name)
    {
	return parent::addElement(new ComboBox($name));
    }
    
    public function addCheckBox($name, $value, $text = null)
    {
	return parent::addElement(new CheckBox($name, $value, $text));
    }
    
    public function addRadioButton($name, $value, $text = null)
    {
	return parent::addElement(new RadioButton($name, $value, $text));
    }
    
    public function addTextArea($name, $text = null)
    {
	return parent::addElement(new TextArea($name, $text));
    }
    
    public function addFileInput($name)
    {
	return parent::addElement(new FileInput($name));
    }
    
    public function addSubmitButton($name = "submit", $label = null)
    {
	return parent::addElement(new SubmitButton($name, $label));
    }

    public function addResetButton($name = "reset", $label = null)
    {
	return parent::addElement(new ResetButton($name, $label));
    }

    public function addStandardButton($name, $label = null)
    {
	return parent::addElement(new StandardButton($name, $label));
    }
    
    public function addButton($type = BUTTON_SUBMIT, $label = null)
    {
	switch($type) {
	 case BUTTON_SUBMIT:
	    return parent::addElement(new SubmitButton($type, $label));
	 case BUTTON_RESET:
	    return parent::addElement(new ResetButton($type, $label));
	 default:
	    return parent::addElement(new StandardButton($type, $label));
	}
    }
    
    public function addSpace()
    {
	return parent::addElement(new BR());
    }

    public function addLine()
    {
	return parent::addElement(new HR());
    }
    
    public function addSectionHeader($text, $class = null)
    {
        $header = parent::addElement(new H4($text));
	$header->setClass(isset($class) ? $class : "secthead");
	return $header;
    }
}

// 
// A table of data.
// 
class Table extends Container
{
    public function __construct()
    {
	parent::__construct("table");
    }
    
    public function setBorder($width)
    {
	parent::setAttr("border", $width);
    }
    
    public function setCellPadding($pixels)
    {
	parent::setAttr("cellpadding", $pixels);
    }
    
    public function setCellSpacing($pixels)
    {
	parent::setAttr("cellspacing", $pixels);
    }
    
    // 
    // See http://www.w3schools.com/tags/tag_table.asp for argument info
    // and the constants TABLE_FRAME_XXX.
    // 
    public function setFrame($str) 
    {
	parent::setAttr("frame", $str);
    }

    // 
    // See http://www.w3schools.com/tags/tag_table.asp for argument info.
    // and the constants TABLE_RULES_XXX.
    // 
    public function setRules($str)
    {
	parent::setAttr("rules", $str);
    }
    
    public function setSummary($text)
    {
	parent::setAttr("summary", $text);
    }
    
    public function addRow()
    {
	return parent::addElement(new TableRow());
    }
    
    public function addTHead()
    {
	return parent::addElement(new THead());
    }

    public function addTFoot()
    {
	return parent::addElement(new TFoot());
    }

    public function addTBody()
    {
	return parent::addElement(new TBody());
    }
}

abstract class TableItem extends Container
{
    public function setAlign($value)
    {
	parent::setAttr("align", $value);
    }
    
    public function setValign($value)
    {
	parent::setAttr("valign", $value);
    }
    
    public function setChar($char)
    {
	parent::setAttr("char", $char);
    }
    
    public function setCharOffset($num)
    {
	parent::setAttr("charoff", $num);
    }
}

class TableRow extends TableItem
{
    public function __construct()
    {
	parent::__construct("tr");
    }
        
    public function addHeader($text)
    {
	return parent::addElement(new TableHeader($text));
    }
    
    public function addData($text = "&nbsp;")
    {
	return parent::addElement(new TableData($text));
    }
}

abstract class TableCell extends TableItem
{
    public function setLink($str, $type = LINK_TYPE_HREF)
    {
	return parent::setLink(new Link($type, $str));
    }
    
    public function setAbbr($text)
    {
	return parent::setAttr("abbr", $text);
    }

    public function setAxis($category)
    {
	return parent::setAttr("axis", $category);
    }
    
    public function setColspan($num)
    {
	return parent::setAttr("colspan", $num);
    }

    public function setRowspan($num)
    {
	return parent::setAttr("rowspan", $num);
    }

    public function setNoWrap()    // Deprecated
    {
	return parent::setAttr("nowrap", "nowrap");
    }
    
    // 
    // See TABLE_ITEM_SCOPE_XXX
    // 
    public function setScope($scope)
    {
	return parent::setAttr("scope", $scope);
    }

    // 
    // This is a convenience function for:
    // setLink($name, LINK_TYPE_NAME)->setTitle($str);
    // 
    public function setAnchor($str, $title)
    {
	parent::setLink(new Link(LINK_TYPE_NAME, $str))->setTitle($title);
    }
}

class TableHeader extends TableCell
{
    public function __construct($text)
    {
	parent::__construct("th");
	parent::addText($text);
    }
}

class TableData extends TableCell
{
    public function __construct($text)
    {
	parent::__construct("td");
	parent::addText($text);
    }

    public function setHeaders($hid)
    {
	return parent::setAttr("headers", $hid);
    }
}

abstract class TContainer extends Container
{
    public function addRow()
    {
	return parent::addElement(new TableRow());
    }
}

class THead extends TContainer
{
    public function __construct() { parent::__construct("thead"); }
}

class TFoot extends TContainer
{
    public function __construct() { parent::__construct("tfoot"); }
}

class TBody extends TContainer
{
    public function __construct() { parent::__construct("tbody"); }
    
    public function setAlign($value)
    {
	parent::setAttr("align", $value);
    }
    
    public function setValign($value)
    {
	parent::setAttr("valign", $value);
    }
    
    public function setChar($char)
    {
	parent::setAttr("char", $char);
    }
    
    public function setCharOffset($num)
    {
	parent::setAttr("charoff", $num);
    }
}

?>
