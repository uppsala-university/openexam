
// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    students.js
// Created: 2015-04-15 03:55:24
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$(document).ready(function () {

    // 
    // Handle column select actions:
    // 
    $(document).on('click', 'ul.dropdown-menu > li > a', function () {
        if ($(this).attr('value') === 'remove') {
            var nth = $(this).closest('th').index() + 1;
            $('td:nth-child(' + nth + '),th:nth-child(' + nth + ')').hide('fast', function () {
                $(this).remove();
            });
        } else {
            $(this).closest('th').attr('value', $(this).attr('value'));
            $(this).parents(".btn-group").find('.btn').text($(this).text());
        }
    });

    // 
    // Handle remove student entries:
    //
    $(document).on('click', 'a.import-student-remove', function () {
        $(this).closest('tr').hide('slow', function () {
            $(this).remove();
        });
    });

    // 
    // Try to map tags to column selectors:
    // 
    $("#table-import-students > tbody > tr:first > td").each(function (column, td) {
        var head = $(this).closest('table').find('th').eq($(this).index());
        if (head.attr('value') === undefined) {
            head.find('a[value=' + $(this).text() + ']').trigger("click");
        }
    });

});
