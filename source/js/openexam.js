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
