/* global Opentip */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    utils.js
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

// 
// Application level utility functions.
// 

/**
 * Custom AJAX function.
 * 
 * @param {String} url The target URL.
 * @param {Json} data The request data.
 * @param {string} target [e.g: undefined, 'return', '#id-of-element', '.class-name']
 * @param {type} type [POST, GET] The request method.
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
        // 
        // Check response status:
        // 
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
        $("#ajax-loader").hide();
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

    if (type === undefined) {
        type = 'info';
    } else if (type === 'error') {
        type = 'danger';
    }

    if (type === 'success') {
        $('#msg-box')
                .attr('class', 'alert alert-' + type)
                .html(message)
                .slideDown(300)
                .delay(2000)
                .slideUp(300);
    } else {
        $('#msg-box')
                .attr('class', 'alert alert-' + type)
                .html(message)
                .slideDown(300);
    }
};

var closeToolTips = function ()
{
    for (var i = 0; i < Opentip.tips.length; i++) {
        Opentip.tips[i].hide();
    }
};

/**
 * Returns length of JSON object
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
 * Global loading and events handlers
 * @param {type} param
 */
$(document).ready(function () {

    $(document).ajaxStart(function () {
        $('#ajax-loader').show();
    });

    $(document).ajaxStop(function () {
        $('#ajax-loader').hide();
    });

    $('.fancybox').fancybox({
        autoHeight: true,
        autoWidth: true,
        helpers: {
            overlay: {
                closeClick: false
            }
        }
    });

    $(document).on('click', '.prevent', function () {
        return false;
    });

    // 
    // Support for localize float point numbers. Uses the language setting in
    // browser to set locale/language for formatting.
    // 
    if (String.prototype.parsefloat === undefined) {
        String.prototype.parsefloat = function () {
            var input = this.replace(',', '.');
            if (Number.parseFloat !== undefined) {
                return Number.parseFloat(input);
            } else {
                return parseFloat(input);
            }
        };
    }

    if (Number.prototype.parsefloat === undefined) {
        Number.prototype.parsefloat = function () {
            var input = String(this).replace(',', '.');
            if (Number.parseFloat !== undefined) {
                return Number.parseFloat(input);
            } else {
                return parseFloat(input);
            }
        };
    }

    if (String.prototype.floatval === undefined) {
        String.prototype.floatval = function () {
            if (navigator.languages !== undefined) {
                return Number(this).toLocaleString(navigator.languages[0]);
            } else if (navigator.language !== undefined) {
                return Number(this).toLocaleString(navigator.language);
            } else {
                return this;
            }
        };
    }

    if (Number.prototype.floatval === undefined) {
        Number.prototype.floatval = function () {
            if (navigator.languages !== undefined) {
                return Number(this).toLocaleString(navigator.languages[0]);
            } else if (navigator.language !== undefined) {
                return Number(this).toLocaleString(navigator.language);
            } else {
                return this;
            }
        };
    }

    // 
    // Popup print dialog:
    // 
    if (window.location.hash && window.location.hash === '#print') {
        window.print();
    }

});
