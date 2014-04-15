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
    if (e.style.display == 'block') {
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
    obj.style.top = posy + 'px';
}

function form_show_result(msgbox, message, fadeout)
{
    $("#result-success").hide();
    $("#result-info").hide();
    $("#result-warn").hide();
    $("#result-error").hide();

    $(".result").show();

    msgbox.children('.mbox-text').html(message);
    msgbox.show();

    if (fadeout) {
        msgbox.delay(1000).fadeOut(400);
    }
}

// 
// Save form using jQuery.
// 
function form_ajax_send(id)
{
    $('#' + id).submit(function(event) {
        event.preventDefault();
        var $form = $(this), action = $form.attr('action'),
                button = $(":input[type=submit]:focus").attr("name"),
                eid = $("input[name=exam]").val(),
                qid = $("input[name=question]").val();

        if (typeof button === 'undefined') {
            button = 'save';
        }

        $.post(action, $form.serialize() + '&ajax=1&' + button + '=1', function() {
        }).done(function(data) {
            var resp = JSON.parse(data);

            if (resp.status === 'ok') {
                form_show_result($("#result-success"), resp.message, true);
                if (button === 'next') {
                    var destination = action + '?exam=' + eid + '&question=' + qid + '&status=ok&next=route';
                    window.location.replace(destination);
                }
            } else if (resp.status === 'info') {
                form_show_result($("#result-info"), resp.message, true);
            } else if (resp.status === 'failed') {
                form_show_result($("#result-warn"), resp.message, false);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            if (typeof $form.attr('bylink') === 'undefined') {
                var message = '<b><u>Failed submit result.</u></b><br/><br/>The web server is probably down, please contact the person responsible for the examination.';
                form_show_result($("#result-error"), message, false);
            }
        })
    });
}

// 
// This function starts an autosave of the form.
// 
function form_auto_save(id, seconds, start)
{
    //
    // Don't post form on first call:
    //
    if (typeof start === 'undefined') {
        $('#' + id).submit();
    }

    //
    // Reschedule call:
    //
    setTimeout("form_auto_save('" + id + "', " + seconds + ")", seconds * 1000);
}

// 
// Attach form submit handler to all links on page.
// 
function form_link_save(id)
{
    $('a').each(function(index, link) {
        $(this).on('click', function() {
            var $form = $('#' + id);
            $form.attr('bylink', link);
            $form.submit();
        });
    });
}

//
// Saved value from focused textbox.
//
var boxdata;

//
// Check that the textbox value is within the given range. Notice that the
// textbox value might having locale dependent representation.
//
function check_range(textbox, min, max)
{
    value = textbox.value.replace(',', '.');

    if (value < min || value > max) {
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
