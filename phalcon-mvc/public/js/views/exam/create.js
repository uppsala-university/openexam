// JavaScript Document specific to Exam create
// @author Ahsan Shahzad [MedfarmDoIT]
// @author Anders Lövgren (BMC-IT)

/*-- var initialization --*/
var totalQs = 0;
var qsJson = {};
var qsCorrectorsJson = {};
var qsIdJson = {};
var qJson = {};
var exam = exam || {};
var formJs = '';
var stEvents = '';

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
    makeQsSortable = function () {
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
    makeQsSortable();

    // 
    // Sort questions; save in database and JSON. Update both in main area and in left menu.
    // 
    var sortQuestions = function () {

        var cntr = 1;
        var tmpJson = {};
        var tmpJson = JSON.parse(JSON.stringify(qsJson));
        var qArr = [];
        $('.sortable-qs').each(function (index, element) {

            if ($(element).find('li').filter(':visible').length) {
                $(element).parent().find('.section-default-msg').hide();
            } else {
                $(element).parent().find('.section-default-msg').show();
            }

            $(element).find('.q').each(function (i, qNo) {

                if ($(qNo).parent().is(':visible')) {
                    tmpJson[cntr] = qsJson[$(qNo).attr('q-no')];
                    qArr.push({'id': qsJson[$(qNo).attr('q-no')]["questId"], "slot": (cntr), "topic_id": $(qNo).closest('.sortable-qs').attr('topic-id')});
                    $(qNo).html("Q" + (cntr) + ":").attr('q-no', (cntr));
                    cntr++;
                }
            });
        });

        // 
        // Send AJAX request to update question's names as per new sorting order.
        // 
        ajax(
                baseURL + 'ajax/core/' + role + '/question/update',
                JSON.stringify(qArr),
                function (qData) {
                    qsJson = JSON.parse(JSON.stringify(tmpJson));
                    refreshQs();
                }
        );

    }

    if (showAddQuestionView && $('.add_new_qs').length) {
        loadQuestionDialog(0);
    }

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
    $('body').on('click', '.bullet-closed', function () {

        $(this).parent().find('.' + $(this).attr('rel')).slideDown(500);
        $(this).removeClass('bullet-closed').addClass('bullet-open').find('img').attr('src', baseURL + 'img/openeopt.png');

        return false;
    });

    $('body').on('click', '.bullet-open', function () {

        $(this).parent().find('.' + $(this).attr('rel')).slideUp(500);
        $(this).removeClass('bullet-open').addClass('bullet-closed').find('img').attr('src', baseURL + 'img/closedopt.png');
        return false;

    });

    // 
    // Exam questions related events:
    // 
    $(".add_new_qs").click(function () {
        loadQuestionDialog(0);
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
        loadQuestionDialog($(this).closest('.qs_area_line').attr('q-no'));
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

            var qAreaLine = $(this).closest('.qs_area_line');

            // 
            // Delete from database and then from JSON:
            // 
            ajax(
                    baseURL + 'ajax/core/' + role + '/question/delete',
                    {"id": qsJson[qNo]["questId"]},
                    function (status) {
                        location.reload();
                        $(qAreaLine).slideUp('500');
                    }
            );
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

        if (start != '') {
            data["starttime"] = start;
        }

        if (end != '') {
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
                    width: "700",
                    position: ['center', 20],
                    modal: true,
                    close: function () {
                        $(this).dialog('destroy');
                        location.reload();
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
    // Question dialog window loading populated data if question id is passed.
    // 
    function loadQuestionDialog(qId)
    {
        var action = (qId ? 'update' : 'create');
        var qDbId = (qId ? qsJson[qId]["questId"] : 0);

        $.ajax({
            type: "POST",
            data: {
                'q_id': qDbId,
                'exam_id': examId,
                'role': role
            },
            url: baseURL + 'question/' + action,
            success: function (content) {

                $("#question-form-dialog-wrap")
                        .attr('q-no', qId)
                        .attr('title', !qId ? 'Add new question.' : 'Update question details.')
                        .html(content);
                $("#question-form-dialog-wrap").dialog({
                    autoOpen: true,
                    width: "60%",
                    minWidth: 400,
                    modal: true,
                    buttons: {
                        "Add new question part": function () {
                            addQuestPartTab();
                        },
                        "Save this question": function () {
                            saveQuestionToExam(qId);
                            $(this).dialog('destroy');
                        },
                        Cancel: function () {
                            closeToolTips();
                            $(this).dialog('destroy');
                        }
                    },
                    close: function () {
                        closeToolTips();
                        $(this).dialog('destroy');
                    }

                });
            }
        });

    }

    // 
    // Functions for adding queation to exam (db storage). It also saves question 
    // data in JSON (on page storage).
    // 
    var saveQuestionToExam = function (qId) {

        var qIndex;

        if (!qId) {
            qIndex = ++totalQs;
            qsJson[qIndex] = {};
            qsCorrectorsJson[qIndex] = {};
        } else {
            qIndex = qId;
        }

        qJson = {};
        var totalQParts = $("#question-form-dialog-wrap").find('.q-part').length;
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
                    CKEDITOR.instances[$(qPart).find('.write_q_ckeditor').attr('id')].getData());
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

        // 
        // Use selected topic or default:
        // 
        if ($('#q-topic-sel').length) {
            var topicId = $('#q-topic-sel').val();
        } else {
            var topicId = $('.topic-name').first().attr('data-id');
        }

        // 
        // Set data for create (qId missing) or question update:
        // 
        if (!qId) {
            data = {"exam_id": examId, "topic_id": topicId, "score": totalScore, "slot": qIndex, "quest": JSON.stringify(qJson), "status": 'active'};
        } else {
            data = {"id": qsJson[qId]["questId"], "score": totalScore, "quest": JSON.stringify(qJson)};
        }

        ajax(
                baseURL + 'ajax/core/' + role + '/question/' + (qId ? 'update' : 'create'),
                data,
                function (qData) {
                    if (qId) {
                        // 
                        // Question was successfully updated. Keep ID and status in JSON object:
                        // 
                        qJson["questId"] = qsJson[qId]["questId"];
                        qJson["status"] = qsJson[qId]["status"];
                    } else {
                        // 
                        // Question was successfully created. Save ID and status in JSON object:
                        // 
                        qJson["questId"] = qData.id;
                        qJson["status"] = qData.status;
                    }

                    // 
                    // Save correctors against this question in database and in JSON object:
                    // 
                    var qCorrectorsArr = [];
                    var qCorrectorList = $('.q_corrector_list');
                    qsCorrectorsJson[qIndex] = {};

                    $(qCorrectorList).find('.left-col-user').each(function (i, rElem) {
                        var correctorUserName = $(rElem).attr('data-user');
                        if (!qId) {
                            qCorrectorsArr.push({'question_id': qData.id, "user": $(rElem).attr('data-user')});
                        }
                        // 
                        // Add correct to json for on page manuplation:
                        // 
                        qsCorrectorsJson[qIndex][i] = $(rElem).html();
                    });

                    // 
                    // Only creator can add correctors!
                    // 
                    if (role == 'creator') {
                        if (!qId && qCorrectorsArr.length) {

                            // 
                            // Send AJAX request to save correctors for this question:
                            // 
                            ajax(
                                    baseURL + 'ajax/core/' + role + '/corrector/create',
                                    JSON.stringify(qCorrectorsArr),
                                    function (userData) {
                                        //do nothing for now
                                    }
                            );
                        }
                    }

                    // 
                    // Finally, add this question to qsJson:
                    // 
                    qJson["canUpdate"] = 1;
                    qsJson[qIndex] = qJson;

                    // 
                    // Refresh main question area:
                    // 
                    refreshQs();

                    // 
                    // Show this question in left menu:
                    // 
                    var qTxtLeftMenu = aPartQtxt.replace(/(<([^>]+)>)/ig, "").substring(0, 75);
                    var qTopic = $('ul[topic-id="' + topicId + '"]');

                    if (!qId) {
                        var newQ = qTopic.find('li:first')
                                .clone()
                                .show()
                                .find('.q')
                                .attr('q-no', qIndex)
                                .html("Q" + qIndex + ":")
                                .end()
                                .find('.q-txt')
                                .html(qTxtLeftMenu)
                                .end();
                        qTopic.append(newQ);
                    } else {
                        qTopic.find('span[q-no="' + qId + '"]').parent().find('.q-txt').html(qTxtLeftMenu);
                    }
                }
        );

    }

    // 
    // Reads question data from JSON object (on page storage) and 
    // re-populates questions in main question area
    // 

    var refreshQs = function () {

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
                    ansTypeHtml = '<img width="70%" src="' + baseURL + 'img/canvas.png">';
                } else {
                    ansTypeHtml = '<img width="70%" src="' + baseURL + 'img/ckeditor.png">';
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

    refreshQs();

});
