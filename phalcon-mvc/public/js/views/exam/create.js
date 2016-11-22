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

/*------------------------------*/
/*	Open tip template	*/
/*------------------------------*/
//initialize tool tip theme
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


/*-- Event handlers --*/
$(document).ready(function () {

    /*------------------------------------------*/
    /*	Sortable questions related				*/
    /*------------------------------------------*/
    makeQsSortable = function () {
        $(".sortable-qs").sortable({
            over: function (event, ui) {
                $(ui.placeholder).closest('ul').parent().find('.section-default-msg').hide();
            },
            stop: function (event, ui) {

                //oldQNo = $(ui.item).find('.q').attr('q-no');
                //topicId = $(ui.item).parent().parent().find('.topic-name').attr('data-id');
                sortQuestions();
                // send ajax requedt to update question's topic
                /*ajax (
                 baseURL + 'core/ajax/creator/question/update',
                 {"id": qsJson[oldQNo]["questId"], "topic_id": topicId},
                 function (qData) {
                 
                 // sort questions in new order and update in db and on page json object
                 sortQuestions();
                 }
                 );*/

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

                //oldQNo = $(ui.item).find('.q').attr('q-no');
                //topicId = $(ui.item).parent().parent().find('.topic-name').attr('data-id');
                var topicArr = [];
                $('.sortable-q-topic > li').each(function (i, topicItem) {
                    topicArr.push({"id": $(topicItem).find('.topic-name').attr('data-id'), "slot": i + 1});
                });

                // send ajax requedt to update question's topic
                ajax(
                        baseURL + 'core/ajax/creator/topic/update',
                        JSON.stringify(topicArr),
                        function (qData) {

                            // sort questions in new order and update in db and on page json object
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

    // sort questions; save in db, json; update both in main area and in left menu
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

        // send ajax request to update question's names as per new sorting order
        ajax(
                baseURL + 'core/ajax/creator/question/update',
                JSON.stringify(qArr),
                function (qData) {
                    qsJson = JSON.parse(JSON.stringify(tmpJson));
                    refreshQs();
                }
        );

    }

    $('body').on('click', "#publish_exam", function () {
        ajax(
                baseURL + 'core/ajax/creator/exam/update',
                {"id": examId, "published": 1},
        function (json) {
            location.href = baseURL + "exam/index";
        }
        );
    });

    //initliaze tooltips in left menu
    $('.search-ldap').each(function (index, element) {
        new Opentip(element,
                'Search by name: <input type="text" class="uu-user-search" isfor="uu-id' + (index + 1) + '">',
                {style: "drops", tipJoint: "top left"});
    });

    /*if($('#exam-settings').length) {
     new Opentip('#exam-settings',
     $('#exam-settings-box').html(),
     {style: "drops", tipJoint: "top right", showOn: "click", });
     }*/

    if (showAddQuestionView && $('.add_new_qs').length) {
        loadQuestionDialog(0);
    }

    //ajx request for searching user
    var userSelected = false;
    $(document).on("keyup.autocomplete", '.uu-user-search', function () {

        $(this).autocomplete({
            //source: "search.php",
            source: function (request, response) {
                respObj = [];
                userList = [];
                ajax(
                        baseURL + 'ajax/catalog/principal',
                        {"data": {"name": request.term + "*"}, "params": {"attr": ["name", "uid", "principal"], "limit": 10}},
                function (json) {

                    $.each(json, function (i, result) {
                        if (userList.indexOf(result.principal) < 0) {
                            respObj.push({"id": result.principal, "label": result.name + " [" + result.uid + "]", "value": result.uid})
                            userList.push(result.principal);
                        }
                    });

                    response(respObj);
                }
                );
                /*		    
                 $.ajax({
                 type: 'GET',
                 url: 'http://media.medfarm.uu.se/play/search_oe.php?callback=?',
                 jsonpCallback: 'jsonCallback',
                 contentType: "application/json",
                 dataType: 'jsonp',
                 data: request,
                 success: function (data) {
                 $("#uu-user-search").removeClass('no-user-found-shadow');
                 $("#uu-user-search").addClass((!data || !data.length) ? 'no-user-found-shadow' : '');
                 response(data);
                 }
                 });*/
            },
            //minLength: 4,
            select: function (event, ui) {

                // empty text box
                $(this).val('');

                // format user's name to be added
                var usernameText = ui.item.label + " [" + ui.item.value + "]";

                addBtnId = $(this).attr('isfor');

                var alreadyExists = false;
                if ($(this).hasClass('q-correctors')) {
                    $(".q_corrector_list").find('li').each(function (index, element) {
                        if ($(element).find('span').attr('data-user') == ui.item.id) {
                            alreadyExists = true;
                        }
                    });
                } else {
                    $("#" + addBtnId).closest('li').find('.menuLevel1').find('.left-col-user').each(function (index, element) {
                        if ($(element).attr('data-user') == ui.item.id) {
                            alreadyExists = true;
                        }
                    });
                }

                if (!alreadyExists) {

                    if ($(this).hasClass('q-correctors')) {
                        cloned = $('.q_corrector_list > li:first').clone()
                                .find('.left-col-user')
                                .attr('data-user', ui.item.id)
                                .html(usernameText)
                                .end();

                        if (qId) {

                            // send ajax request to add selected corrector in question
                            ajax(
                                    baseURL + 'core/ajax/creator/corrector/create',
                                    {"question_id": qId, "user": ui.item.id},
                            function (status) {
                                $('.q_corrector_list').append(cloned);
                            }
                            );

                        } else {
                            //alert("no qid");
                            $('.q_corrector_list').append(cloned);
                        }

                    } else {

                        // send ajax request to save added role
                        model = $("#" + $(this).attr('isfor')).closest('a').attr('data-model');
                        ajax(
                                baseURL + 'core/ajax/creator/' + model + '/create',
                                {"exam_id": examId, "user": ui.item.id},
                        function (userData) {

                            // prepare item to be added
                            tempItem = $("#" + addBtnId)
                                    .closest('li')
                                    // hide default message, if it was visible
                                    .find('.menuLevel1')
                                    .find('.left-col-def-msg')
                                    .hide()
                                    .end()

                                    // find template item and prepare it to add
                                    .find('li:first')
                                    .clone()

                                    // update data-ref attribute; helpful in deletion
                                    .find('.deluuid')
                                    .attr('data-ref', userData.id)
                                    .end()
                                    .show()
                                    //update username data
                                    .find('.left-col-user')
                                    .attr('data-user', ui.item.id)
                                    .html(usernameText)
                                    .show()
                                    .end();

                            // add item to the menu
                            $("#" + addBtnId).closest('li').find('.menuLevel1').show().append(tempItem);


                            // close all tooltips
                            //closeTooltips();
                        }
                        );
                    }
                }

                return false;
            },
            close: function (event, ui) {
                // do nothing for now
            }
        });
    });

    // delete selected uuid 
    $('body').on('click', ".deluuid", function () {

        if ($(this).closest('.menuLevel1').find('li:visible').length <= 1) {
            $(this).closest('.menuLevel1').find('.left-col-def-msg').show();
        }

        // send ajax request to delete this record
        model = $(this).closest('.menuLevel1').parent().find('a').attr('data-model');
        reqUrl = baseURL + 'core/ajax/creator/' + model + '/delete';
        thisItem = $(this);
        ajax(reqUrl, {"id": $(this).attr('data-ref')}, function (json) {
            $(thisItem).closest('li').remove();
        });

    });

    /*--------------------------------------------------*/
    /*	Editable text - @toDo: clean/replace/delete		*/
    /*--------------------------------------------------*/

    var charWidth = 8;
    var textBoxMax = 300;
    $('body').on("click", ".editabletext", function () {
        if (!$(this).find(".editabletextbox").length) {
            var txt = ($(this).attr("default") == $(this).html()) ? "" : $(this).html().replace("<br>", "\n", "g");
            //console.log(txt);

            var fontSize = parseFloat(window.getComputedStyle(this, null).getPropertyValue('font-size'));
            //console.log(fontSize);

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

    $('body').on('blur', '.editabletextbox', function () { // replace field with text
        var tmp = (!$(this).val().length) ? $(this).parent().attr("default") : $(this).val().replace("\n", "<br />", "g");
        //$(this).parent().hide().html(tmp).slideDown(300);
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
        } else {
            //console.log("key is being pressed");
        }
    });

    $('body').on('keypress', '.editabletextbox', function (e) {
        if (e.which == 13) { // Enter key
            if (!$(this).is('textarea')) {
                e.preventDefault();
            }
        }
    });



    /*------------------------------------------*/
    /*		Left menu related		*/
    /*------------------------------------------*/
    $('body').on('click', '.bullet-closed', function () {
        /*
         var t = 1;
         var bullet = $(this);
         $('.menuLevel1').slideUp(300, function() {
         if(t++ == $('.menuLevel1').length) {
         $(bullet).parent().find('.menuLevel1').toggle();
         $(bullet).find('img').attr('src', baseURL+'img/closedopt.png');
         }
         });*/

        $(this).parent().find('.' + $(this).attr('rel')).slideDown(500);
        $(this).removeClass('bullet-closed').addClass('bullet-open').find('img').attr('src', baseURL + 'img/openeopt.png');

        return false;

    });

    $('body').on('click', '.bullet-open', function () {

        $(this).parent().find('.' + $(this).attr('rel')).slideUp(500);
        $(this).removeClass('bullet-open').addClass('bullet-closed').find('img').attr('src', baseURL + 'img/closedopt.png');
        return false;

    });




    /*------------------------------------------*/
    /*		Exam questions related events		*/
    /*------------------------------------------*/
    // add new
    $(".add_new_qs").click(function () {

        loadQuestionDialog(0);
        return false;

    });
    // edit a question
    $(document).on('click', '.edit-q', function () {

        loadQuestionDialog($(this).closest('.qs_area_line').attr('q-no'));
        return false;

    });
    // delete question
    $(document).on('click', '.del-q', function () {

        // delete question from json
        var qNo = $(this).closest('.qs_area_line').attr('q-no');
        if (confirm("Are you sure you want to delete question no. " + qNo + "?")) {

            qAreaLine = $(this).closest('.qs_area_line');

            // delete from database and then from json
            ajax(
                    baseURL + 'core/ajax/contributor/question/delete',
                    {"id": qsJson[qNo]["questId"]},
            function (status) {

                location.reload();
                $(qAreaLine).slideUp('500');

                /*delete qsJson[qNo];
                 
                 i = 1;
                 jQuery.each(qsJson, function (newQNo, qData) {
                 qsJson[i++] = qData;
                 });
                 
                 // hide from central area
                 $(qAreaLine).slideUp('500', function () {
                 
                 // hide from left menu
                 $('.sortable-qs').find('span[q-no="' + qNo + '"]').parent().remove();
                 
                 sortQuestions();
                 });*/

            }
            );


        }
        return false;

    });



    /*----------------------------------------------*/
    /*	Exam settings related events 				*/
    /*----------------------------------------------*/
    // exam title update
    /*$('.exam-title').blur(function () {
     
     oldVal = $(this).attr('old-value');
     newVal = $(this).val();
     
     if (newVal != oldVal) {
     ajax(
     baseURL + 'core/ajax/creator/exam/update',
     {"id": examId, "name": newVal},
     function (examData) {
     $('#exam-title').attr('old-value', examData.name)
     }
     );
     }
     
     });*/

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
                    /*						buttons: {
                     "Save": function() {
                     },
                     "btn ": function() {
                     },
                     Cancel: function() {
                     $(this).dialog('destroy');
                     }
                     },*/
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

    /*   
     if($('#exam-desc').length) {
     // exam description update
     CKEDITOR.instances['exam-desc'].on('blur', function (event) {
     
     oldVal = $('#exam-desc').attr('old-val');
     newVal = CKEDITOR.instances['exam-desc'].getData();
     
     if (newVal != oldVal) {
     ajax(
     baseURL + 'core/ajax/creator/exam/update',
     {"id": examId, "descr": newVal},
     function (examData) {
     $('#exam-desc').attr('old-value', examData.descr)
     }
     );
     }
     
     });
     }
     */
    // @ToDO: move this even handler to settings.phtml 	
    // exam's other setting's update
    $(document).on('click', '.save-exam-settings', function () {

        //if(jQuery("#exam-settings-box").validationEngine('validate')) {
        // get data to be saved
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

        //
        ajax(
                baseURL + 'core/ajax/creator/exam/update',
                data,
                function (examData) {
                    closeTooltips();
                }
        );
        //} else {
        //	alert(jQuery("#exam-settings-box").validationEngine('validate'));
        //}
    });


    /*----------------------------------------------*/
    /*	Manage exam students related events    	    */
    /*----------------------------------------------*/
    $(document).on('click', '.manage-students', function () {

        $.ajax({
            type: "POST",
            data: {'exam_id': examId},
            url: baseURL + 'exam/students/',
            success: function (content) {
                $("#mng-students").html(content);
                $("#mng-students").dialog({
                    autoOpen: true,
                    width: "50%",
                    position: ['center', 20],
                    modal: true,
                    /*						buttons: {
                     "Save": function() {
                     },
                     "btn ": function() {
                     },
                     Cancel: function() {
                     $(this).dialog('destroy');
                     }
                     },*/
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



    /*----------------------------------------------*/
    /*	Generalized  events 			*/
    /*----------------------------------------------*/
    $('body').on("focus", ".datepicker", function () {
        //$(this).datepicker();
        $(this).datetimepicker({
            controlType: 'select',
            timeFormat: 'HH:mm',
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            minDate: 0
        });
    });


    /*------------------------------------------------------------------*/
    /*	Loads question dialog window									*/
    /*	It loads populated data of question, if question id is passed	*/
    /*------------------------------------------------------------------*/

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
                    width: "80%",
                    /*position: ['center', 10],*/
                    modal: true,
                    buttons: {
                        "Add new question part": function () {
                            addQuestPartTab();
                        },
                        "I am done, save this question": function () {

                            // add question to qsJson and in database
                            saveQuestionToExam(qId);

                            // close popup window
                            $(this).dialog('destroy');
                        },
                        Cancel: function () {
                            close_tooltips();
                            $(this).dialog('destroy');
                        }
                    },
                    close: function () {
                        //allFields.val( "" ).removeClass( "ui-state-error" );
                        close_tooltips();
                        $(this).dialog('destroy');
                    }

                });
            }
        });

    }
    ;


    /*----------------------------------------------------------*/
    /*	Functions for adding queation to exam (db storage)		*/
    /*	It also saves question data in json (on page storage)	*/
    /*----------------------------------------------------------*/

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

        // get data of each question part
        $("#question-form").find('.q-part').each(function (index, qPart) {
            // make questiom part title e.g a/b/c
            qPartTitle = String.fromCharCode(96 + (index + 1));

            // initiate js object that will populate later on
            qJson[qPartTitle] = {};
            qJson[qPartTitle]["ans_area"] = {};
            qJson[qPartTitle]["resources"] = {};

            //get question text (html)
            var qText = jQuery.trim(
                    CKEDITOR.instances[$(qPart).find('.write_q_ckeditor').attr('id')].getData());
            qJson[qPartTitle]["q_text"] = qText;
            aPartQtxt = aPartQtxt == '' ? qText : aPartQtxt;

            // get question resources
            var qResourcesList = $(qPart).find('.q_resources > ul');
            if ($(qResourcesList).find('li').length) {
                qJson[qPartTitle]["resources"] = {};
                $(qResourcesList).find('li > a').each(function (i, rElem) {
                    qJson[qPartTitle]["resources"][$(rElem).html()] = $(rElem).attr('file-path');
                });
            } else {
                qJson[qPartTitle]["resources"] = [];
            }

            // get answer type
            var ansType = $(qPart).find('input[class=ans_type_selector]:checked');
            qJson[qPartTitle]["ans_area"]["type"] = $(ansType).val();

            // populate answer area related data in Json object
            if ($(ansType).val() === 'choicebox') {
                qJson[qPartTitle]["ans_area"]["data"] = {};
                $(ansType).parent().parent().find('.ans_type').find('.question_opts > div > span').each(function (i, optElement) {
                    qJson[qPartTitle]["ans_area"]["data"][$(optElement).html()] = $(optElement).parent().find('input').is(':checked');
                });
            } else if ($(ansType).val() === 'textarea') {
                qJson[qPartTitle]["ans_area"]["data"] = [];
                qJson[qPartTitle]["ans_area"]["word_count"] = $(ansType).parent().parent().find('input[name="word-count"]:checked').val();
            } else {
                qJson[qPartTitle]["ans_area"]["data"] = [];
            }

            // find and sum up score for this part
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
                baseURL + 'core/ajax/contributor/question/' + (qId ? 'update' : 'create'),
                data,
                function (qData) {
                    // question was successfully added, save question id in json object
                    qJson["questId"] = qId ? qsJson[qId]["questId"] : qData.id;

                    // save correctors against this question in database and in json object
                    var qCorrectorsArr = [];
                    var qCorrectorList = $('.q_corrector_list');
                    qsCorrectorsJson[qIndex] = {};

                    $(qCorrectorList).find('.left-col-user').each(function (i, rElem) {
                        var correctorUserName = $(rElem).attr('data-user');
                        if (!qId) {
                            qCorrectorsArr.push({'question_id': qData.id, "user": $(rElem).attr('data-user')});
                        }

                        // add correct to json for on page manuplation
                        qsCorrectorsJson[qIndex][i] = $(rElem).html();
                    });

                    if (!qId && qCorrectorsArr.length) {

                        // send ajax request to save correctors for this question
                        ajax(
                                baseURL + 'core/ajax/creator/corrector/create',
                                JSON.stringify(qCorrectorsArr),
                                function (userData) {
                                    //do nothing for now
                                }
                        );
                    }


                    // finally, add this question to qsJson
                    qJson["canUpdate"] = 1;
                    qsJson[qIndex] = qJson;

                    // refresh main question area
                    refreshQs();

                    // 	show this question in left menu
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

        // lets remove all questions
        $('.qs_area_line:visible').remove();

        // hide default message now 
        totalQs = objectLength(qsJson);
        if (totalQs) {
            $('#default_msg_qs').hide();
            $('#exam_op_btns').show();
        } else {
            $('#default_msg_qs').show();
            $('#exam_op_btns').hide();
        }

        // get data of each question part
        jQuery.each(qsJson, function (qNo, qData) {

            // clone first line that was kept hidden
            var qLine = $('.qs_area_line:first').clone();
            $(qLine).attr('q-no', qNo).find('.q_no').html('Q' + qNo + ':').end();

            if (!qData["canUpdate"]) {
                console.log(qData["canUpdate"]);
                $(qLine).find('.q_line_op').remove();
            }

            var totalScore = 0;
            var firstPartQText = '';
            var totalQParts = objectLength(qData) - 2; //we have 2 extra nodes in qParts json (on page, not in db): questId,canUpdate

            jQuery.each(qData, function (qPartTitle, qPartData) {

                // skip extra node
                if (qPartTitle == 'questId' || qPartTitle == 'canUpdate') {
                    if (qPartTitle == 'questId') {
                        $(qLine).attr('q-id', qPartData);
                    }
                    return;
                }

                //get question text (html)
                var qText = qPartData.q_text;

                // get question resources

                // get answer type
                var ansType = qPartData.ans_area["type"];

                // clone question part line
                var qPartLine = $(qLine).find('.qs_area_line_q').filter(':first').clone();

                // find and sum up score for this part
                qPartScore = qPartData.q_points;
                totalScore += qPartScore;

                //get answer fields
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