// 
// Javascript functions for OpenExam PHP.
// 
// Author: Anders Lövgren
// Date:   2010-06-04
// 

// 
// Hide or show an element tagged by id.
// From: http://blog.movalog.com/a/javascript-toggle-visibility/
// 
function toggle_visibility(id) {
        var e = document.getElementById(id);
        if(e.style.display == 'block') {
                e.style.display = 'none';
        }
        else {
                e.style.display = 'block';
        }
}

//
// Move object to current mouse position.
//
function move_object(obj, event) {
        var posx = 0;
        var posy = 0;
        var e = event || window.event;

        if (e.pageX || e.pageY) {
                posx = e.pageX;
                posy = e.pageY;
        } else if (e.clientX || e.clientY) {
                posx = e.clientX + document.body.scrollLeft
                + document.documentElement.scrollLeft;
                posy = e.clientY + document.body.scrollTop
                + document.documentElement.scrollTop;
        }
        //
        // posx and posy contains the mouse position relative to the document.
        // 
        obj.style.left = posx + 'px';
        obj.style.top  = posy + 'px';
}

// 
// This function starts an autosave of the form.
// 
function autosave_form(name, seconds, start)
{
        var form = document.getElementById(name);
	
        //
        // Don't post form on first call:
        //
        if(start == null) {
                form.autosave.value = true;
                form.submit();
        }
	
        //
        // Reschedule call:
        //
        var timeout = seconds * 1000;
        var timer   = setTimeout("autosave_form('" + name + "', " + seconds + ")", timeout);
}

//
// Saved value from focused textbox.
//
var boxdata;

//
// Check that the textbox value is within the given range.
//
function check_range(textbox, min, max)
{
        if(textbox.value < min || textbox.value > max) {
                alert('Value must be between ' + min + ' and ' + max);
                textbox.value = boxdata;
        }
}

//
// Save the value in textbox. This function is typical called whe triggered
// by an onfocus event. This is the buddy function to check_range().
//
function start_check(textbox)
{
        boxdata = textbox.value;
}
