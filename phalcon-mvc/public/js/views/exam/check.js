/* global examId, baseURL */

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    check.js
// Created: 2017-02-16 16:07:47
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

// 
// Exam status check.
// 

$(document).ready(function () {

    // 
    // Show dialog:
    // 
    $('.exam-check').click(function () {
        $.ajax({
            type: "POST",
            data: {exam_id: examId},
            url: baseURL + 'exam/check',
            success: function (content) {
                showDialogWindow("#exam-check-box", content);
            }
        });

    });

});
