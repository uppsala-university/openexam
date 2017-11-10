/* global baseURL, role, examId */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    user-search.js
// Created: 2017-02-23 04:16:57
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

// 
// Store tooltips in array for dynamic content update:
// 
var opentips = [];

// 
// Attach user search tooltip (using index) on element:
// 
function attachCatalogSearch(index, element)
{
    var userman = $("#user-insert-box").clone();
    var opentip = $(element).opentip({
        style: "drops",
        tipJoint: "top left"
    });

    opentip.isfor = 'uu-id' + (index + 1);

    userman.find('input.user-search').attr('isfor', opentip.isfor);
    opentip.setContent(userman.html());

    opentips.push(opentip);
}

// 
// Insert new staff user in tooltip dialog.
// 
function insertStaffUser(item)
{
    if (item.mail === undefined) {
        item.mail = '';
    }
    if (item.name === undefined) {
        item.name = item.id;
    }

    // 
    // Check if already present:
    // 
    $('#user-insert-box').find('.user-staff-add').each(function () {
        if ($(this).attr('data-user') === item.id) {
            item.exist = true;
            return false;
        }
    });
    if (item.exist !== undefined) {
        return;
    }

    // 
    // Create new user entry and append to user-insert-box template:
    // 
    var insert = $('#user-insert-box').find('.user-insert-staff > div');
    var uentry = insert.find(".user-staff-entry").first().clone();
    var anchor = uentry.find('.user-staff-add');
    anchor.attr('data-mail', item.mail);
    anchor.attr('data-user', item.id);
    anchor.attr('data-name', item.name);
    anchor.text(item.name + ' [' + item.id + ']');
    insert.append(uentry);

    // 
    // Refresh tooltip content from user-insert-box template:
    // 
    for (var i = 0; i < opentips.length; ++i) {
        var opentip = opentips[i];
        var userman = $("#user-insert-box").clone();
        userman.find('input.user-search').attr('isfor', opentip.isfor);
        opentip.setContent(userman.html());
    }

}

function removeStaffUser(item)
{
    $(".user-insert-staff").each(function () {

    });
}

// 
// Search users in catalog. Return in response callback.
// 
function searchCatalogUser(term, response)
{
    var respObj = [];
    var userList = [];
    ajax(
            baseURL + 'ajax/catalog/principal',
            {
                data: {
                    name: term + "*"
                },
                params: {
                    attr: ["name", "uid", "principal", "mail"],
                    limit: 10
                }
            },
            function (json) {

                var checked = $('.user-insert-show-mail').is(':checked');

                $.each(json, function (i, result) {
                    if (userList.indexOf(result.principal) < 0) {
                        respObj.push({
                            id: result.principal,
                            label: checked ? (result.name + ' [' + result.mail[0] + ']') : (result.name + ' [' + result.principal + ']'),
                            value: result.principal,
                            name: result.name,
                            mail: result.mail[0]
                        });
                        userList.push(result.principal);
                    }
                });
                response(respObj);
            });
}

// 
// Insert user from catalog. The item contains data and element is the target menu list.
// 
function insertCatalogUser(item, element)
{
    element.val('');

    var isfor = element.attr('isfor');
    var anchor = $("#" + isfor);
    var model = anchor.attr('data-model');

    if (model === undefined) {
        alert("Failed lookup associated data model");
    } else if (model === 'corrector') {
        if (!hasCorrector(item, anchor)) {
            addCorrector(item, anchor, model);
        }
    } else {
        if (!hasUserRole(item, anchor)) {
            addUserRole(item, anchor, model);
        }
    }
}

// 
// Check if corrector exist on question.
// 
function hasCorrector(item, anchor)
{
    item.exists = false;

    anchor.closest('div').find('.left-col-user').each(function (index, element) {
        if ($(element).attr('data-user') === item.id) {
            item.exists = true;
            return false;
        }
    });

    return item.exists;
}

// 
// Add corrector on question.
// 
function addCorrector(item, anchor, model)
{
    var qid = anchor.attr('qid');
    var entry = $('.q_corrector_list > li:first').clone()
            .show(200)
            .find('.left-col-user')
            .attr('data-user', item.id)
            .html(item.name)
            .end();

    if (qid) {
        // 
        // Send AJAX request to add corrector of question:
        // 
        ajax(
                baseURL + 'ajax/core/' + role + '/corrector/create',
                {
                    "question_id": qid,
                    "user": item.id
                },
                function (status) {
                    entry.find('.left-col-user').attr('data-rec', status.id);
                    $('.q_corrector_list').append(entry);
                    insertStaffUser(item);
                });
    } else {
        $('.q_corrector_list').append(entry);
    }
}

// 
// Check if user has role.
// 
function hasUserRole(item, anchor)
{
    item.exists = false;

    anchor.closest('li').find('.left-col-user').each(function (index, element) {
        if ($(element).attr('data-user') === item.id) {
            item.exists = true;
            return false;
        }
    });

    return item.exists;
}

// 
// Add user role on exam.
// 
function addUserRole(item, anchor, model)
{
    // 
    // Send AJAX request to save added role:
    // 
    ajax(
            baseURL + 'ajax/core/' + role + '/' + model + '/create',
            {
                exam_id: examId,
                user: item.id
            },
            function (response) {

                // 
                // Prepare item to be added:
                // 
                var entry = anchor.closest('li')
                        // 
                        // Hide default message, if it was visible:
                        // 
                        .find('.menuLevel1')
                        .find('.left-col-def-msg')
                        .hide()
                        .end()

                        // 
                        // Find template item and prepare it to add:
                        // 
                        .find('li:first')
                        .clone()

                        // 
                        // Update data-ref attribute; helpful in deletion:
                        // 
                        .find('.deluuid')
                        .attr('data-ref', response.id)
                        .end()
                        .show()

                        // 
                        // Update username data:
                        // 
                        .find('.left-col-user')
                        .attr('data-user', item.id)
                        .html(item.name)
                        .show()
                        .end();

                // 
                // Add item to the menu:
                // 
                anchor.closest('li').find('.menuLevel1').show().append(entry);

                // 
                // Append to staff list:
                // 
                insertStaffUser(item);
            });

}

$(document).ready(function () {

    // 
    // Add user top menu from list:
    // 
    $(document).on('click', '.user-staff-add', function () {
        var elem = $(this).closest('.user-insert-staff').parent().find('input.user-search');
        var item = {
            id: $(this).attr('data-user'),
            name: $(this).attr('data-name')
        };
        insertCatalogUser(item, elem);
        return false;
    });

    // 
    // Add user top menu from input textbox:
    // 
    $(document).on('click', '.user-search-add', function () {
        var elem = $(this).parent().find('input.user-search');
        var item = {
            id: elem.val(),
            name: elem.val()
        };
        insertCatalogUser(item, elem);
        return false;
    });

    // 
    // Show email address instead of username in user saerch dialog:
    // 
    $(document).on('click', '.user-insert-show-mail', function () {
        var checked = $(this).is(':checked');
        $('.user-staff-add').each(function () {
            if (checked) {
                $(this).text($(this).attr('data-name') + ' [' + $(this).attr('data-mail') + ']');
            } else {
                $(this).text($(this).attr('data-name') + ' [' + $(this).attr('data-user') + ']');
            }
        });
    });

    // 
    // Handle incremental catalog user search.
    // 
    $(document).on("keyup.autocomplete", '.user-search', function () {
        var element = $(this);
        $(this).autocomplete({
            source: function (request, response) {
                searchCatalogUser(request.term, response);
            },
            select: function (event, ui) {
                insertCatalogUser(ui.item, element);
                return false;
            },
            close: function (event, ui) {
                // Do nothing for now.
            }
        });
    });
});
