
// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
            data: {'exam_id': examId},
            url: baseURL + 'exam/check',
            success: function (content) {
                $("#exam-check-box").html(content);
                $("#exam-check-box").dialog({
                    autoOpen: true,
                    width: "50%",
                    modal: true
                });
            }
        });

    });

});
