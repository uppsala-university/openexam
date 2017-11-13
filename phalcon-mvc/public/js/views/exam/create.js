/* global mode, baseURL, role, showAddQuestionView, examId, isNewExam, CKEDITOR, qId */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    create.js
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

// 
// Javascript specific to exam create.
// 

var totalQs = 0;
var qsJson = {};
var qsIdJson = {};
var qJson = {};
var exam = exam || {};
var formJs = '';

$(document).ready(function () {

    // 
    // Handle organization change:
    // 
    $(document).on('input', "input[list='user-department-list']", function () {
        var depart = $(this).val();
        $("#user-department-select > option").each(function () {
            if (depart === $(this).val()) {
                $("input[name='exam-code']").val($(this).attr('data-code'));
            }
        });
    });

    // 
    // Compatibility for non HTML5 browsers:
    // 
    $(document).on('change', '#user-department-select', function () {
        var depart = $(this).val();
        $("#user-department-select > option").each(function () {
            if (depart === $(this).val()) {
                $("input[name='exam-code']").val($(this).attr('data-code'));
            }
        });
        $("input[list='user-department-list']").val(depart);
    });

    // 
    // Initialize opentip for adding users for roles:
    // 
    $('.search-catalog-service').each(function (index, element) {
        attachCatalogSearch(index, element);
    });

    // 
    // Fix document location when mode has been passed in URL:
    // 
    if (history.pushState) {
        if (mode.length > 0) {
            if (location.href.indexOf(mode) > 0) {
                history.pushState({state: mode}, '', location.href.replace(mode, ''));
            }
        }
    }

    // 
    // Sortable questions related.
    // 
    makeQuestionSortable = function () {
        $(".sortable-qs").sortable({
            over: function (event, ui) {
                $(ui.placeholder).closest('ul').parent().find('.section-default-msg').hide();
            },
            stop: function (event, ui) {
                sortQuestions();
            },
            connectWith: ".sortable-qs",
            forcePlaceholderSize: true,
            opacity: 0.5,
            placeholder: "sortable-placeholder"
        });

        $(".sortable-qs").disableSelection();

        $(".sortable-q-topic").sortable({
            over: function (event, ui) {
                $(ui.placeholder).closest('ul').parent().find('.section-default-msg').hide();
            },
            stop: function (event, ui) {
                var topicArr = [];
                $('.sortable-q-topic > li').each(function (i, topicItem) {
                    topicArr.push({"id": $(topicItem).find('.topic-name').attr('data-id'), "slot": i + 1});
                });

                // 
                // Send AJAX request to update question's topic:
                // 
                ajax(
                        baseURL + 'ajax/core/' + role + '/topic/update',
                        JSON.stringify(topicArr),
                        function (qData) {

                            // 
                            // Sort questions in new order and update in db and on page JSON object:
                            // 
                            sortQuestions();
                        }
                );

            },
            forcePlaceholderSize: true,
            opacity: 0.5,
            placeholder: "sortable-placeholder"
        });

    };
    makeQuestionSortable();

    // 
    // Remap question data indexes.
    // 
    var remapQuestionData = function (qMap) {
        var newJson = {};

        for (var i = 0; i < qMap.length; ++i) {
            newJson[i + 1] = qsJson[qMap[i]];
        }

        qsJson = newJson;
    };

    // 
    // Sort questions; save in database and JSON. Update both in main area and in left menu.
    // 
    var sortQuestions = function () {
        var qArr = [];  // Question data for update.
        var qMap = [];  // Question index remap.
        var topicId = $('.sortable-qs').attr('topic-id');

        $('.sortable-qs > li:visible').each(function (index, element) {
            var item = $(element);

            qArr.push({id: item.attr('q-id'), slot: index + 1, topic_id: topicId});
            qMap.push(item.find('.q').attr('q-no'));
            item.find('.q').html('Q' + (index + 1) + ':');
            item.find('.q').attr('q-no', index + 1);
        });

        // 
        // Send AJAX request to update question's order.
        // 
        ajax(
                baseURL + 'ajax/core/' + role + '/question/update',
                JSON.stringify(qArr),
                function (qData) {
                    remapQuestionData(qMap);
                    refreshQuestions();
                }
        );

    };

    // 
    // Delete selected UUID:
    // 
    $('body').on('click', ".deluuid", function () {

        if ($(this).closest('.menuLevel1').find('li:visible').length <= 1) {
            $(this).closest('.menuLevel1').find('.left-col-def-msg').show();
        }

        // 
        // Send AJAX request to delete this record:
        // 
        var model = $(this).closest('.menuLevel1').parent().find('a').attr('data-model');
        var reqUrl = baseURL + 'ajax/core/' + role + '/' + model + '/delete';
        var thisItem = $(this);
        ajax(reqUrl, {"id": $(this).attr('data-ref')}, function (json) {
            $(thisItem).closest('li').remove();
        });

    });

    // 
    // Clear content and remove editable class of inline edit element.
    // 
    $('body').on("click", ".editabletext", function () {
        $(this).removeClass("editabletext");
        $(this).text("");
    });

    // 
    // Left menu related:
    // 
    $('body').on('click', '.fa-caret-right', function () {
        var list = $(this).closest('li').find('ul');
        var icon = $(this);

        list.slideDown(500);
        icon.removeClass('fa-caret-right').addClass('fa-caret-down');

        return false;
    });

    $('body').on('click', '.fa-caret-down', function () {
        var list = $(this).closest('li').find('ul');
        var icon = $(this);

        list.slideUp(500);
        icon.removeClass('fa-caret-down').addClass('fa-caret-right');

        return false;
    });

    // 
    // Exam questions related events:
    // 
    $(".add_new_qs").click(function () {
        newQuestion();
        return false;
    });

    // 
    // View question:
    // 
    $(document).on('click', '.view-q', function () {
        var url = baseURL + '/exam/' + examId + '/question/' + $(this).closest('.qs_area_line').attr('q-id');
        document.location = url;
        return false;
    });

    // 
    // Edit question:
    // 
    $(document).on('click', '.edit-q', function () {
        editQuestion($(this).closest('.qs_area_line').attr('q-no'));
        return false;
    });

    // 
    // Remove question (set status removed):
    // 
    $(document).on('click', '.remove-q', function () {
        if (confirm("Do you want to remove this question from the exam?\r\n"
                + "You can insert the question back at any time. If you remove the question, then "
                + "it will no longer be visable and answarable during the exam for students.\r\n"
                + "Removed questions are no longer included in grading during correction, but any already "
                + "saved answers are not deleted.")) {
            var question = $(this).closest('.qs_area_line');
            var qid = question.attr('q-id');
            var qbody = question.find('.qs_area_line_q_parts');

            ajax(
                    baseURL + 'ajax/core/' + role + '/question/update',
                    {
                        id: qid,
                        status: "removed"
                    },
                    function (status) {
                        qbody.addClass("question-removed");
                        question.find(".remove-q").hide();
                        question.find(".insert-q").show();
                        question.find('.edit-q').addClass('editable');
                    }
            );
        }
        return false;
    });

    // 
    // Insert question (set status active):
    // 
    $(document).on('click', '.insert-q', function () {
        if (confirm("Do you want to insert this question back on the exam?\r\n"
                + "If you insert this question, then it will become visible for students during "
                + "the exam. Any already saved answers will be accessable and during the correction "
                + "this question will be included in grading during correction.")) {
            var question = $(this).closest('.qs_area_line');
            var qid = question.attr('q-id');
            var qbody = question.find('.qs_area_line_q_parts');

            ajax(
                    baseURL + 'ajax/core/' + role + '/question/update',
                    {
                        id: qid,
                        status: "active"
                    },
                    function (status) {
                        qbody.removeClass("question-removed");
                        question.find(".insert-q").hide();
                        question.find(".remove-q").show();
                        question.find('.edit-q').removeClass('editable');
                    }
            );

        }
        return false;
    });

    // 
    // Delete question:
    // 
    $(document).on('click', '.del-q', function () {
        var qNo = $(this).closest('.qs_area_line').attr('q-no');

        if (confirm("Are you sure you want to delete question no. " + qNo + "?")) {
            deleteQuestion(qsJson[qNo]["questId"], function () {
                location.reload();
                $(this).closest('.qs_area_line').slideUp('500');
            });
        }
        return false;
    });

    // 
    // Exam settings related events.
    // 
    $('.exam-settings').click(function () {

        $.ajax({
            type: "POST",
            data: {'exam_id': examId},
            url: baseURL + 'exam/settings/',
            success: function (content) {
                $("#exam-settings-box").html(content);
                $("#exam-settings-box").dialog({
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

    if (isNewExam) {
        $('.exam-settings').first().trigger('click');
    }

    $(document).on('click', '.check-exam', function () {
        $('.exam-check').trigger('click');
    });

    // 
    // The exam archive download/view online dialog and its related event handlers.
    // 
    $(document).on('click', '.exam-archive', function () {
        $('#exam-archive-box').dialog({
            autoOpen: true,
            width: "50%",
            modal: true,
            close: function () {
                $(this).dialog('destroy');
            }
        });
    });

    $(document).on('click', '#close-archive', function () {
        $('#exam-archive-box').dialog('destroy');
    });

    $(document).on('click', '#exam-archive-download', function () {
        document.getElementById('exam-archive-action-download').click();
    });

    $(document).on('click', '#exam-archive-online', function () {
        document.getElementById('exam-archive-action-online').click();
    });

    // TODO: Move this even handler to settings.phtm

    // 
    // Exam's other setting's update:
    // 
    $(document).on('click', '.save-exam-settings', function () {
        var examDesc;

        var settingBox = $(this).closest('.exam-settings-box');
        var examTitle = $(settingBox).find('input[name="exam-title"]').val();

        if (CKEDITOR.instances['exam-desc']) {
            examDesc = CKEDITOR.instances['exam-desc'].getData();
        }

        var org = $(settingBox).find('input[name="unit"]').val();
        var start = $(settingBox).find('input[name="start"]').val();
        var end = $(settingBox).find('input[name="end"]').val();
        var grades = $(settingBox).find('textarea[name="grade"]').val();
        var code = $(settingBox).find('input[name="exam-code"]').val();
        var course = $(settingBox).find('input[name="exam-course-code"]').val();
        var details = 0;

        $(settingBox).find('input[name="details[]"]:checked').each(function (index, element) {
            details += Number($(element).val());
        });

        data = {"id": examId, "name": examTitle, "descr": examDesc, "orgunit": org, "grades": grades, "details": details, "course": course, "code": code};

        if (start !== '') {
            data["starttime"] = start;
        }

        if (end !== '') {
            data["endtime"] = end;
        }

        ajax(
                baseURL + 'ajax/core/' + role + '/exam/update',
                data,
                function (examData) {
                    closeToolTips();
                }
        );
    });

    // 
    // Manage exam students related events.
    // 
    $(document).on('click', '.manage-students', function () {

        $.ajax({
            type: "POST",
            data: {'exam_id': examId},
            url: baseURL + 'exam/students/',
            success: function (content) {
                $("#manage-students").html(content);
                $("#manage-students").dialog({
                    autoOpen: true,
                    width: "750",
                    position: ['center', 20],
                    modal: true,
                    close: function () {
                        $(this).dialog('destroy');
                        location.reload();
                    }
                });
            },
            error: function (error) {
                $("#manage-students").html(error.responseText);
                $("#manage-students").dialog({
                    autoOpen: true,
                    width: "50%",
                    position: ['center', 20],
                    modal: true,
                    close: function () {
                        $(this).dialog('destroy');
                    },
                    show: {
                        effect: "blind",
                        duration: 5
                    },
                    hide: {
                        effect: "blind",
                        duration: 5
                    }
                });
            }
        });
    });

    // 
    // Generalized event handlers.
    // 
    $('body').on("focus", ".datepicker", function () {
        $(this).datetimepicker({
            controlType: 'select',
            timeFormat: 'HH:mm',
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            minDate: 0
        });
    });

    // 
    // Delete from database and then callback:
    // 
    var deleteQuestion = function (qid, callback) {
        ajax(
                baseURL + 'ajax/core/' + role + '/question/delete',
                {
                    id: qid
                },
                function (status) {
                    if (callback) {
                        callback();
                    }
                }
        );
    };

    // 
    // Create new question:
    // 
    var newQuestion = function () {
        loadQuestion(0, 'create', 0);
    };

    // 
    // Edit existing question:
    // 
    var editQuestion = function (pos) {
        loadQuestion(pos, 'update', qsJson[pos]["questId"]);
    };

    // 
    // Question dialog window. 
    // 
    // This function is called when creating or updating a question. The controller will 
    // create a empty question and return HTML for further editing if this function is 
    // called qith qid == 0.
    // 
    var loadQuestion = function (pos, action, qid)
    {
        var target = $("#question-form-dialog-wrap");
        var title = 'Edit question';

        $.ajax({
            type: "POST",
            data: {
                'q_id': qid,
                'exam_id': examId,
                'role': role
            },
            url: baseURL + 'question/' + action,
            success: function (content) {
                target
                        .attr('q-no', pos)
                        .attr('title', title)
                        .html(content);

                // 
                // Get question ID from AJAX loaded HTML content:
                // 
                if (!qid) {
                    qid = qId;
                }

                // 
                // Delete question created by AJAX call when dialog is closed and user
                // confirms closing without saving.
                // 
                target.dialog({
                    autoOpen: true,
                    width: "60%",
                    minWidth: 400,
                    modal: true,
                    buttons: {
                        "Add new question part": function () {
                            addQuestPartTab();
                        },
                        "Save this question": function () {
                            saveQuestion(pos, qid);
                            $(this).dialog('destroy');
                        }
                    },
                    beforeClose: function () {
                        var close = true;

                        if (action === 'create') {
                            close = confirm("Question has not yet been saved. Close this dialog anyway?");
                        }

                        return close;
                    },
                    close: function () {
                        closeToolTips();
                        $(this).dialog('destroy');

                        if (action === 'create') {
                            deleteQuestion(qid);
                        }
                    }
                });
            },
            error: function (error) {
                target.html(error.responseText);
                target.dialog({
                    autoOpen: true,
                    modal: true
                });
            }
        });

    };

    // 
    // Functions for adding question to exam and saving question data in JSON (on page storage).
    // 
    var saveQuestion = function (pos, qid) {

        var qIndex;

        if (!pos) {
            qIndex = ++totalQs;
            qsJson[qIndex] = {};
        } else {
            qIndex = pos;
        }

        qJson = {};
        var totalScore = 0;
        var aPartQtxt = '';

        // 
        // Get data of each question part:
        // 
        $("#question-form").find('.q-part').each(function (index, qPart) {
            // 
            // Make questiom part title e.g a/b/c:
            // 
            var qPartTitle = String.fromCharCode(96 + (index + 1));

            // 
            // Initiate js object that will populate later on:
            // 
            qJson[qPartTitle] = {};
            qJson[qPartTitle]["ans_area"] = {};
            qJson[qPartTitle]["resources"] = {};

            // 
            // Get question text (html):
            // 
            var qText = jQuery.trim(
                    CKEDITOR.instances[$(qPart).find('.write_q_ckeditor').attr('id')].getData()
                    );
            qJson[qPartTitle]["q_text"] = qText;
            aPartQtxt = aPartQtxt === '' ? qText : aPartQtxt;

            // 
            // Get question resources:
            // 
            var qResourcesList = $(qPart).find('.lib_resources > ul');
            if ($(qResourcesList).find('li').length) {
                qJson[qPartTitle]["resources"] = {};
                $(qResourcesList).find('li > a').each(function (i, rElem) {
                    qJson[qPartTitle]["resources"][$(rElem).html()] = $(rElem).attr('file-path');
                });
            } else {
                qJson[qPartTitle]["resources"] = [];
            }

            // 
            // Get canvas background:
            // 
            var qCanvasImage = $(qPart).find('.lib_canvas > ul > li > a').first();
            if (qCanvasImage) {
                qJson[qPartTitle]["ans_area"]["back"] = {};
                qJson[qPartTitle]["ans_area"]["back"][qCanvasImage.html()] = qCanvasImage.attr('file-path');
            }

            // 
            // Get answer type:
            // 
            var ansType = $(qPart).find('input[class=ans_type_selector]:checked');
            qJson[qPartTitle]["ans_area"]["type"] = $(ansType).val();

            // 
            // Populate answer area related data in JSON object:
            // 
            if ($(ansType).val() === 'choicebox') {
                qJson[qPartTitle]["ans_area"]["data"] = {};
                $(ansType).parent().parent().find('.ans_type').find('.question_opts > div > div').each(function (i, optElement) {
                    qJson[qPartTitle]["ans_area"]["data"][$(optElement).html()] = $(optElement).parent().find('input').is(':checked');
                });
            } else if ($(ansType).val() === 'textarea') {
                qJson[qPartTitle]["ans_area"]["data"] = [];
                qJson[qPartTitle]["ans_area"]["word_count"] = $(ansType).parent().parent().find('input[name="word-count"]:checked').val();
                qJson[qPartTitle]["ans_area"]["spell_check"] = $(ansType).parent().parent().find('input[name="spell-check"]:checked').val() === "on";
            } else {
                qJson[qPartTitle]["ans_area"]["data"] = [];
            }

            // 
            // Find and sum up score for this part:
            // 
            var qPartScore = $(qPart).find('.q-part-points').val().parsefloat();
            qJson[qPartTitle]["q_points"] = qPartScore;
            totalScore += qPartScore;
        });

        // 
        // Send AJAX request to add/update this question in database. Prepare question
        // data and send request. Add or update qJson to global qsJson if successful.
        // 

//        // 
//        // Use selected topic or default:
//        // 
//        if ($('#q-topic-sel').length) {
//            var topicId = $('#q-topic-sel').val();
//        } else {
//            var topicId = $('.topic-name').first().attr('data-id');
//        }
//
        // 
        // Set data for question update:
        // 
        data = {
            id: qid,
            score: totalScore,
            quest: JSON.stringify(qJson)
        };

        ajax(
                baseURL + 'ajax/core/' + role + '/question/update',
                data,
                function (qData) {
                    if (pos) {
                        // 
                        // Question was successfully updated. Keep ID and status in JSON object:
                        // 
                        qJson["questId"] = qsJson[pos]["questId"];
                        qJson["status"] = qsJson[pos]["status"];
                    } else {
                        // 
                        // Question was successfully created. Save ID and status in JSON object:
                        // 
                        qJson["questId"] = qData.id;
                        qJson["status"] = qData.status;
                    }

                    // 
                    // Add this question to JSON:
                    // 
                    qJson["canUpdate"] = 1;
                    qsJson[qIndex] = qJson;

                    // 
                    // Refresh main question area:
                    // 
                    refreshQuestions();

                    // 
                    // Show this question in left menu:
                    // 
                    var qTxtLeftMenu = aPartQtxt.replace(/(<([^>]+)>)/ig, "").substring(0, 75);
                    var qTopic = $('ul[topic-id]').first();

                    if (!pos) {
                        var newQ = qTopic.find('li:first')
                                .clone()
                                .show()
                                .attr('q-id', qid)
                                .find('.q')
                                .attr('q-no', qIndex)
                                .html("Q" + qIndex + ":")
                                .end()
                                .find('.q-txt')
                                .html(qTxtLeftMenu)
                                .end();
                        qTopic.append(newQ);
                    } else {
                        qTopic.find('span[q-no="' + pos + '"]').parent().find('.q-txt').html(qTxtLeftMenu);
                    }
                }
        );

    };

    // 
    // Reads question data from JSON object (on page storage) and 
    // re-populates questions in main question area
    // 

    var refreshQuestions = function () {

        // 
        // Remove all questions:
        // 
        $('.qs_area_line:visible').remove();

        // 
        // Hide default message now:
        // 
        totalQs = objectLength(qsJson);
        if (totalQs) {
            $('#default_msg_qs').hide();
            $('#exam_op_btns').show();
        } else {
            $('#default_msg_qs').show();
            $('#exam_op_btns').hide();
        }

        // 
        // Get data of each question part:
        // 
        jQuery.each(qsJson, function (qNo, qData) {
            // 
            // Clone first line that was kept hidden:
            // 
            var qLine = $('.qs_area_line:first').clone();
            $(qLine).attr('q-no', qNo).find('.q_no').html('Q' + qNo + ':').end();

            if (!qData["canUpdate"]) {
                $(qLine).find('.q_line_op').remove();
            }

            if (qData.status === "removed") {
                $(qLine).find('.remove-q').hide();
                $(qLine).find('.insert-q').show();
                $(qLine).find('.edit-q').addClass('editable');
                $(qLine).find('.qs_area_line_q_parts').addClass("question-removed");
            }
            if (qData.status === "active") {
                $(qLine).find('.insert-q').hide();
                $(qLine).find('.remove-q').show();
            }

            var totalScore = 0;
            var firstPartQText = '';

            // 
            // We have 2 extra nodes in qParts json (on page, not in db): questId, canUpdate
            // 
            var totalQParts = objectLength(qData) - 3;

            jQuery.each(qData, function (qPartTitle, qPartData) {

                // 
                // Skip extra node:
                // 
                if (qPartTitle === 'questId' || qPartTitle === 'canUpdate' || qPartTitle === 'status') {
                    if (qPartTitle === 'questId') {
                        $(qLine).attr('q-id', qPartData);
                    }
                    return;
                }

                // 
                // Get question text (html):
                // 
                var qText = qPartData.q_text;

                // 
                // Get answer type:
                // 
                var ansType = qPartData.ans_area["type"];

                // 
                // Clone question part line:
                // 
                var qPartLine = $(qLine).find('.qs_area_line_q').filter(':first').clone();

                // 
                // Find and sum up score for this part:
                // 
                qPartScore = qPartData.q_points;
                totalScore += qPartScore;

                // 
                // Get answer fields:
                // 
                var ansTypeHtml = '';
                if (ansType === 'textbox') {
                    ansTypeHtml = '<input disabled type="text" style="width:350px">';

                } else if (ansType === 'choicebox') {
                    var totalCorrect = 0;
                    jQuery.each(qPartData.ans_area["data"], function (optTitle, optionStatus) {
                        ansTypeHtml += '<div style="padding-top:5px; ' + (optionStatus ? 'color: green; ' : '') + '">\
                                           ' + (optionStatus ? '<i class="fa fa-check-circle fa-lg"></i>' : '<input type="checkbox" ' + (optionStatus ? 'checked' : '') + ' disabled>') + '\
                                            <span>' + optTitle + '</span>\
                                       </div>';

                        if (optionStatus) {
                            totalCorrect++;
                        }
                    });

                    if (totalCorrect === 1) {
                        ansTypeHtml = ansTypeHtml.replace(new RegExp(/type=\"checkbox\"/g), 'type=\"radio\"');
                    }
                } else if (ansType === 'canvas') {
                    ansTypeHtml = '<img width="55%" src="' + baseURL + 'img/canvas.png">';
                } else {
                    ansTypeHtml = '<img width="80%" src="' + baseURL + 'img/ckeditor.png">';
                }

                $(qPartLine)
                        .find('.q_title').html(qText).end()
                        .find('.q_fields').html(ansTypeHtml);

                if (totalQParts > 1) {
                    $(qPartLine).find('.q_part_no').html(qPartTitle + '.');
                    $(qPartLine).find('.q_part_pt').html("(" + qPartData.q_points.floatval() + ' pt.)');
                }


                $(qLine).find('.qs_area_line_q_parts').append($(qPartLine).show());

                if (firstPartQText === '') {
                    firstPartQText = qText;
                }

            });

            $(qLine).find('.qs_area_line_q:last').css('border-bottom', '2px solid #fbeed5');
            $(qLine).find('.q_score').html('[' + totalScore.floatval() + ' pt.]');

            $('#qs_area').append($(qLine).show());

        });
    };

    refreshQuestions();

});
