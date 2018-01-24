/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
            head.find('a[value="' + $(this).text() + '"]').trigger("click");
        }
    });

    // 
    // Remove first row if first column contains 'user' or personal number:
    // 
    var row = $("#table-import-students > tbody > tr:first");
    $("#table-import-students > tbody > tr:first > td").each(function (column, td) {
        switch ($(td).text()) {
            case 'user':
            case 'persnr':
            case 'pnr':
                row.remove();
                break;
        }
    });

});
