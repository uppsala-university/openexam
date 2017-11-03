// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    security.js
// Created: 2015-04-07 22:56:58
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

// 
// Exam security management.
// 

$(document).ready(function () {

    // 
    // Show dialog:
    // 
    $('.exam-security').click(function () {
        $.ajax({
            type: "POST",
            data: {'exam_id': examId},
            url: baseURL + 'exam/security',
            success: function (content) {
                $("#exam-security-box").html(content);
                $("#exam-security-box").dialog({
                    autoOpen: true,
                    width: "50%",
                    modal: true,
                    close: function () {
                        $(this).dialog('destroy');
                    }
                });
            }
        });

    });

    // 
    // Enable/disable lockdown:
    // 
    $('body').on('click', "#lockdown-enable", function () {
        // TODO: disable edit of child elements.
    });

    // 
    // Remove location entry from list:
    // 
    $('body').on('click', '#location-remove', function () {
        $(this).closest('tr').attr('entry', 'remove');
        $(this).closest('tr').hide();
    });

    // 
    // Add (insert) location entry in list from popup:
    // 
    $('body').on('click', '#location-add', function () {
        var button = "<span id='location-remove' class='btn btn-success' style='padding:2px; font-size:11px; min-width: 6em'><i class='fa fa-cut'></i><span>Remove</span></span>";
        var item = $(this).closest('li');
        $('body').find('#locations-table').append("<tr entry='new'><td contenteditable='true'>" + item.attr('disp') + "</td><td contenteditable='true'>" + item.attr('addr').replace(';', '<br/>') + "</td><td>" + button + "</td></tr>");
    });

    // 
    // Create new empty location entry in list:
    // 
    $('body').on('click', '#location-new', function () {
        var button = "<span id='location-remove' class='btn btn-success' style='padding:2px; font-size:11px; min-width: 6em'><i class='fa fa-cut'></i><span>Remove</span></span>";
        $('body').find('#locations-table').append("<tr entry='new'><td contenteditable='true' placeholder='Write location name'></td><td contenteditable='true' placeholder='Replace with IP-address'></td><td>" + button + "</td></tr>");
    });

    // 
    // Display insert entry popup:
    // 
    $('body').on('click', '#location-insert', function () {
        $('#locations-list').dialog({
            autoOpen: true,
            width: "30%",
            modal: true,
            close: function () {
                $(this).dialog('destroy');
            }
        });
    });

    // 
    // Display details in entry popup:
    // 
    $('body').on('click', '#locations-details', function () {
        $('.location-addresses').toggle('fast');
    });

    // 
    // Save settings (if requested) and close dialog:
    // 
    $('body').on('click', "#close-security", function () {
        if ($(this).hasClass("save")) {

            // 
            // Save lockdown parameters:
            // 
            var lockdown = {
                'enable': $(document).find('#lockdown-enable').attr('checked') === 'checked',
                'method': $(document).find('#lockdown-method').val()
            };

            ajax(
                    baseURL + 'ajax/core/creator/exam/update',
                    {
                        "id": examId,
                        "lockdown": JSON.stringify(lockdown)
                    }, function () {
            }, 'POST', true, false);

            // 
            // Save access list (from table):
            // 
            var add = [], update = [], remove = [];
            $('body').find('#locations-table tr').each(function () {
                var item = $(this);
                switch (item.attr('entry')) {
                    case 'new':
                        add.push({
                            'exam_id': examId,
                            'name': item.find('td:eq(0)').text().replace(/\s*\-\>\s*/g, ';'),
                            'addr': item.find('td:eq(1)').html().replace(/\<br\/?\>/g, ';')
                        });
                        break;
                    case 'update':
                        update.push({
                            'id': item.attr('id'),
                            'name': item.find('td:eq(0)').text().replace(/\s*\-\>\s*/g, ';'),
                            'addr': item.find('td:eq(1)').html().replace(/\<br\/?\>/g, ';')
                        });
                        break;
                    case 'remove':
                        remove.push({
                            'id': item.attr('id')
                        });
                        break;
                }
            });

            if (add.length > 0) {
                ajax(
                        baseURL + 'ajax/core/creator/access/create',
                        JSON.stringify(add), function () {
                }, 'POST', true, false);
            }
            if (update.length) {
                ajax(
                        baseURL + 'ajax/core/creator/access/update',
                        JSON.stringify(update), function () {
                }, 'POST', true, false);
            }
            if (remove.length) {
                ajax(
                        baseURL + 'ajax/core/creator/access/delete',
                        JSON.stringify(remove), function () {
                }, 'POST', true, false);
            }

            showMessage('Security settings updated successful', 'success');
        }
        $("#exam-security-box").dialog('close');
    });

});
