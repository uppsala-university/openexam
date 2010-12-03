// 
// Javascript functions for mtapps.
// 
// Author: Anders Lövgren
// Date:   2010-12-02
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
