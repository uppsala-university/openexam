/**
 * Application level utility functions
 * 
 * @author Ahsan Shahzad (MedfarmDoIT)
 **/


/**
 * Ajax wrapper
 * 
 * @param {String} url
 * @param {Json} data
 * @param {string} target [e.g: undefined, 'return', '#id-of-element', '.class-name']
 * @param {type} type [POST, GET]
 */
var ajax = function (url, data, callback, type, async, showSuccessMsg) {

    type = typeof type !== 'undefined' ? type : 'POST';
    async = typeof async !== 'undefined' ? async : true;
    showSuccessMsg = typeof showSuccessMsg !== 'undefined' ? showSuccessMsg : true;

    var request = $.ajax({
        url: url,
        type: type,
        data: data,
        dataType: "json",
        async: async
    });

    request.done(function (response) {
        // check response status
        if (typeof response.failed !== "undefined") {
            showMessage(response.failed.return, 'error');
        } else if (typeof response.success !== "undefined") {

            callback(response.success.return);

            if (showSuccessMsg) {
                if (response.success.action === 'create') {
                    showMessage("Data has been inserted.", 'success');
                } else if (response.success.action === 'update') {
                    showMessage("Updated successfully.", 'success');
                } else if (response.success.action === 'delete') {
                    showMessage("Record has been successfully deleted.", 'success');
                }
            }
        } else {
            showMessage('Request failed. Please contact system administrators.', 'error');
        }
    });

    request.fail(function (jqXHR, textStatus) {
        showMessage('Request failed. Please contact system administrators: ' + textStatus, 'error');
        $("#ajax_loader").hide();
    });
};

/**
 * Shows a message for 3 seconds
 * 
 * @param {String} message
 * @param {String} message
 * @returns 
 */
var showMessage = function (message, type) {

    type = typeof type !== 'undefined' ? type : 'info';

    if (type == 'success') {
        $('#msg-box')
                .attr('class', 'alert alert-' + type)
                .html(message)
                .slideDown(300)
                .delay(3000)
                .slideUp(300);
    } else {
        $('#msg-box')
                .attr('class', 'alert alert-' + type)
                .html(message)
                .slideDown(300)
    }
}

/**
 * Helper to close all opened tooltips
 * @returns 
 */
var closeTooltips = function () {
    for (var i = 0; i < Opentip.tips.length; i++) {
        Opentip.tips[i].hide();
    }
}

/**
 * Returns length of json object
 * 
 * @param {json} object
 * @returns {Number}
 */
function objectLength(object)
{
    var length = 0;
    for (var key in object) {
        if (object.hasOwnProperty(key)) {
            ++length;
        }
    }
    return length;
}

/**
 * Closes all opened opentip instances
 * 
 */
function close_tooltips()
{
    for (var i = 0; i < Opentip.tips.length; i++) {
        Opentip.tips[i].hide();
    }
}

/**
 * Global loading and events handlers
 * @param {type} param
 */
$(document).ready(function () {

    $(document).ajaxStart(function () {
        $('#ajax_loader').show();
    });

    $(document).ajaxStop(function () {
        $('#ajax_loader').hide();
    });

    $('.fancybox').fancybox({
        autoHeight: true,
        autoWidth: true, helpers: {
            overlay: {closeClick: false}
        }
    });

    $(document).on('click', '.prevent', function () {
        return false;
    });

});
