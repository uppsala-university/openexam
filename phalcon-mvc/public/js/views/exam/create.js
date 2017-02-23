// JavaScript Document specific to Exam create
// @Author Ahsan Shahzad [MedfarmDoIT]

/*-- var initialization --*/
var totalQs = 0;
var qsJson = {};
var qsCorrectorsJson = {};
var qsIdJson = {};
var qJson = {};
var exam = exam || {};
var formJs = '';
var stEvents = '';

// 
// Initialize tool tip theme.
// 
Opentip.styles.drops = {
    className: "drops",
    borderWidth: 1,
    stemLength: 5,
    stemBase: 10,
    borderRadius: 5,
    background: "#F6F6F6",
    borderColor: "#CCCCCC",
    target: true,
    tipJoint: "bottom",
    targetJoint: "top",
    containInViewport: false,
    showOn: "click",
    hideOn: "click",
    closeButtonOffset: [3, 3],
    closeButtonCrossColor: "#CF3B18",
    closeButtonCrossSize: 5,
    closeButtonCrossLineWidth: 2,
    group: "tags",
    hideTrigger: 'closeButton'
};

$(document).ready(function () {

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

        cntr = 1;
        tmpJson = {};
        tmpJson = JSON.parse(JSON.stringify(qsJson));
        qArr = [];
        $('.sortable-qs').each(function (index, element) {

            if ($(element).find('li').filter(':visible').length) {
                $(element).parent().find('.section-default-msg').hide();
            } else {
                $(element).parent().find('.section-default-msg').show();
            }

            $(element).find('.q').each(function (i, qNo) {

                if ($(qNo).parent().is(':visible')) {
                    //console.log(qsJson[$(qNo).attr('q-no')]["questId"]+"---"+$(qNo).parent().attr('q-id')+"---->"+$(this).parent().find('.q-txt').html());
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
    // Store tooltips in array for dynamic content update:
    // 
    var opentips = [];

    // 
    // Initialize opentip for adding users for roles:
    // 
    $('.search-catalog-service').each(function (index, element) {
        var opentip = $(this).opentip({style: "drops", tipJoint: "top left"});
        var userman = $("#user-insert-box").clone();

        opentip.isfor = 'uu-id' + (index + 1);
        userman.find('input.user-search').attr('isfor', opentip.isfor);
        opentip.setContent(userman.html());

        opentips.push(opentip);
    });

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
    // Insert new staff user in tooltip dialog.
    // 
    function insertStaffUser(item) {
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
        var insert = $('#user-insert-box').find('.user-insert-staff');
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

    function removeStaffUser(item) {
        $(".user-insert-staff").each(function () {

        });
    }

    // 
    // Search users in catalog. Return in response callback.
    // 
    function searchCatalogUser(term, response) {
        var respObj = [];
        var userList = [];

        ajax(
                baseURL + 'ajax/catalog/principal',
                {
                    data: {
                        name: term + "*"
                    },
                    params: {
                        attr: ["name", "uid", "principal"],
                        limit: 10
                    }
                },
        function (json) {

            $.each(json, function (i, result) {
                if (userList.indexOf(result.principal) < 0) {
                    respObj.push({
                        id: result.principal,
                        label: result.name + ' [' + result.principal + ']',
                        value: result.principal,
                        name: result.name,
                        mail: result.mail
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
        var addBtnId = element.attr('isfor');

        var alreadyExists = false;

        if (element.hasClass('q-correctors')) {
            $(".q_corrector_list").find('li').each(function (index, element) {
                if ($(element).find('span').attr('data-user') == item.id) {
                    alreadyExists = true;
                }
            });
        } else {
            $("#" + addBtnId).closest('li').find('.menuLevel1').find('.left-col-user').each(function (index, element) {
                if ($(element).attr('data-user') == item.id) {
                    alreadyExists = true;
                }
            });
        }

        if (alreadyExists) {
            return;
        }

        if (element.hasClass('q-correctors')) {
            cloned = $('.q_corrector_list > li:first').clone()
                    .find('.left-col-user')
                    .attr('data-user', item.id)
                    .html(item.name)
                    .end();

            if (qId) {

                // 
                // Send AJAX request to add selected corrector in question:
                // 
                ajax(
                        baseURL + 'ajax/core/' + role + '/corrector/create',
                        {"question_id": qId, "user": item.id},
                function (status) {
                    $('.q_corrector_list').append(cloned);
                }
                );

            } else {
                $('.q_corrector_list').append(cloned);
            }

        } else {

            // 
            // Send AJAX request to save added role:
            // 
            model = $("#" + element.attr('isfor')).closest('a').attr('data-model');
            ajax(
                    baseURL + 'ajax/core/' + role + '/' + model + '/create',
                    {"exam_id": examId, "user": item.id},
            function (userData) {

                // 
                // Prepare item to be added:
                // 
                tempItem = $("#" + addBtnId)
                        .closest('li')
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
                        .attr('data-ref', userData.id)
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
                $("#" + addBtnId).closest('li').find('.menuLevel1').show().append(tempItem);
            }
            );
        }

        insertStaffUser(item);
    }

    // 
    // Handle incremental catalog user search.
    // 
    $(document).on("keyup.autocomplete", '.user-search', function () {

        $(this).autocomplete({
            source: function (request, response) {
                searchCatalogUser(request.term, response);
            },
            select: function (event, ui) {
                insertCatalogUser(ui.item, $(this));
                return false;
            },
            close: function (event, ui) {
                // Do nothing for now.
            }
        });
    });

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
        model = $(this).closest('.menuLevel1').parent().find('a').attr('data-model');
        reqUrl = baseURL + 'ajax/core/' + role + '/' + model + '/delete';
        thisItem = $(this);
        ajax(reqUrl, {"id": $(this).attr('data-ref')}, function (json) {
            $(thisItem).closest('li').remove();
        });

    });

    var charWidth = 8;
    var textBoxMax = 300;
    $('body').on("click", ".editabletext", function () {
        if (!$(this).find(".editabletextbox").length) {
            var txt = ($(this).attr("default") == $(this).html()) ? "" : $(this).html().replace("<br>", "\n", "g");

            var fontSize = parseFloat(window.getComputedStyle(this, null).getPropertyValue('font-size'));

            var len = (!$(this).attr('editboxsize')) ? (((txt.length * charWidth) >= textBoxMax) ? textBoxMax : (txt.length ? (txt.length * charWidth) : ($(this).attr("default").length * charWidth))) : $(this).attr('editboxsize');

            $(this).attr("old_val", $(this).html());

            if ($(this).hasClass('textarea')) {
                len = 740;
                $(this).html('<textarea rows=4 cols=5 class="editabletextbox" style="width:' + len + 'px; border:1px solid #E1E1E1;"></textarea>');
            } else {
                $(this).html('<input type="text" class="editabletextbox" style="width:' + len + 'px; font-size:' + fontSize + 'px; border:1px solid #E1E1E1;" />');
            }
            $(this).find(".editabletextbox").hide().val(txt).slideDown(300).attr('placeholder', $(this).attr("default")).focus();
        }
    });

    $('body').on('blur', '.editabletextbox', function () {
        // 
        // Replace field with text:
        // 
        var tmp = (!$(this).val().length) ? $(this).parent().attr("default") : $(this).val().replace("\n", "<br />", "g");
        $(this).parent().html(tmp);
    });

    $('body').on('keyup', '.editabletextbox', function (e) {
        if (e.which == 13) { // Enter key
            if (!$(this).is('textarea')) {
                $(this).trigger('focusout');
            }
        } else if (e.which == 27) { // Escape key. Return previous value
            $(this).parent().html($(this).parent().attr('old_val'));
            $('#qtext_tmp').val(formToKurt());
        }
    });

    $('body').on('keypress', '.editabletextbox', function (e) {
        if (e.which == 13) { // Enter key
            if (!$(this).is('textarea')) {
                e.preventDefault();
            }
        }
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
    // Delete question:
    // 
    $(document).on('click', '.del-q', function () {
        var qNo = $(this).closest('.qs_area_line').attr('q-no');
        if (confirm("Are you sure you want to delete question no. " + qNo + "?")) {

            qAreaLine = $(this).closest('.qs_area_line');

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

    // TODO: Move this even handler to settings.phtm

    // 
    // Exam's other setting's update:
    // 
    $(document).on('click', '.save-exam-settings', function () {
        var examDesc;

        settingBox = $(this).closest('.exam-settings-box');
        examTitle = $(settingBox).find('input[name="exam-title"]').val();

        if (CKEDITOR.instances['exam-desc']) {
            examDesc = CKEDITOR.instances['exam-desc'].getData();
        }

        org = $(settingBox).find('input[name="unit"]').val();
        start = $(settingBox).find('input[name="start"]').val();
        end = $(settingBox).find('input[name="end"]').val();
        grades = $(settingBox).find('textarea[name="grade"]').val();
        code = $(settingBox).find('input[name="exam-code"]').val();
        course = $(settingBox).find('input[name="exam-course-code"]').val();
        details = 0;

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
                    closeTooltips();
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
                    width: "50%",
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
        qDbId = (qId ? qsJson[qId]["questId"] : 0);

        $.ajax({
            type: "POST",
            data: {'q_id': qDbId, 'exam_id': examId},
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
                        "I am done, save this question": function () {
                            saveQuestionToExam(qId);
                            $(this).dialog('destroy');
                        },
                        Cancel: function () {
                            close_tooltips();
                            $(this).dialog('destroy');
                        }
                    },
                    close: function () {
                        close_tooltips();
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

        /////////// Create Json object for Q ///////////
        //	initializations
        ///////////////////////////////////////////////
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
            qPartTitle = String.fromCharCode(96 + (index + 1));

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
            aPartQtxt = aPartQtxt == '' ? qText : aPartQtxt;

            // 
            // Get question resources:
            // 
            var qResourcesList = $(qPart).find('.q_resources > ul');
            if ($(qResourcesList).find('li').length) {
                qJson[qPartTitle]["resources"] = {};
                $(qResourcesList).find('li > a').each(function (i, rElem) {
                    qJson[qPartTitle]["resources"][$(rElem).html()] = $(rElem).attr('file-path');
                });
            } else {
                qJson[qPartTitle]["resources"] = [];
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
                $(ansType).parent().parent().find('.ans_type').find('.question_opts > div > span').each(function (i, optElement) {
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
            qPartScore = Number($(qPart).find('.q-part-points').val());
            qJson[qPartTitle]["q_points"] = qPartScore;
            totalScore += qPartScore;

        });

        /////////// Send ajax request to add/update this question in database ///////////////////
        //	prepare exam data and send request. Add/update qJson to global qsJson if successful
        /////////////////////////////////////////////////////////////////////////////////////////
        if ($('#q-topic-sel').length) {
            topicId = $('#q-topic-sel').val();
        } else {
            topicId = $('#default_topic_id').val();
        }

        if (!qId) {
            data = {"exam_id": examId, "topic_id": topicId, "score": totalScore, "name": qIndex, "quest": JSON.stringify(qJson), "status": 'active'};
        } else {
            data = {"id": qsJson[qId]["questId"], "score": totalScore, "quest": JSON.stringify(qJson)};
        }

        ajax(
                baseURL + 'ajax/core/' + role + '/question/' + (qId ? 'update' : 'create'),
                data,
                function (qData) {
                    // 
                    // Question was successfully added, save question id in JSON object:
                    // 
                    qJson["questId"] = qId ? qsJson[qId]["questId"] : qData.id;

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
                    qTxtLeftMenu = aPartQtxt.replace(/(<([^>]+)>)/ig, "").substring(0, 75);
                    if (!qId) {
                        newQ = $('.sortable-q-topic > li:last').find('.sortable-qs > li:first')
                                .clone()
                                .show()
                                .find('.q')
                                .attr('q-no', qIndex)
                                .html("Q" + qIndex + ":")
                                .end()
                                .find('.q-txt')
                                .html(qTxtLeftMenu)
                                .end();
                        $('.sortable-q-topic > li:last').find('.sortable-qs').append(newQ);
                    } else {
                        $('.sortable-qs').find('span[q-no="' + qId + '"]').parent().find('.q-txt').html(qTxtLeftMenu);
                    }

                }
        );

    }

    // 
    // Reads question data from json object (on page storage) and 
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
                console.log(qData["canUpdate"]);
                $(qLine).find('.q_line_op').remove();
            }

            var totalScore = 0;
            var firstPartQText = '';

            // 
            // We have 2 extra nodes in qParts json (on page, not in db): questId, canUpdate
            // 
            var totalQParts = objectLength(qData) - 2;

            jQuery.each(qData, function (qPartTitle, qPartData) {

                // 
                // Skip extra node:
                // 
                if (qPartTitle == 'questId' || qPartTitle == 'canUpdate') {
                    if (qPartTitle == 'questId') {
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
                ansTypeHtml = '';
                if (ansType == 'textbox') {
                    ansTypeHtml = '<input disabled type="text" style="width:350px">';

                } else if (ansType == 'choicebox') {
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

                    if (totalCorrect == 1) {
                        ansTypeHtml = ansTypeHtml.replace(new RegExp(/type=\"checkbox\"/g), 'type=\"radio\"');
                    }
                } else if (ansType == 'canvas') {
                    ansTypeHtml = '<img width="70%" src="' + baseURL + 'img/canvas.png">';
                } else {
                    ansTypeHtml = '<img width="70%" src="' + baseURL + 'img/ckeditor.png">';
                }

                $(qPartLine)
                        .find('.q_title').html(qText).end()
                        .find('.q_fields').html(ansTypeHtml);

                if (totalQParts > 1) {
                    $(qPartLine).find('.q_part_no').html(qPartTitle + '.');
                    $(qPartLine).find('.q_part_pt').html("(" + qPartData.q_points + ' pt.)');
                }


                $(qLine).find('.qs_area_line_q_parts').append($(qPartLine).show());

                if (firstPartQText == '') {
                    firstPartQText = qText;
                }

            });

            $(qLine).find('.qs_area_line_q:last').css('border-bottom', '2px solid #fbeed5');
            $(qLine).find('.q_score').html('[' + totalScore + ' pt.]');

            $('#qs_area').append($(qLine).show());

        });
    }
    refreshQs();

});
