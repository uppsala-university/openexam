/* global filter, expand, baseURL */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    index.js
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

// 
// Javascript specific to exam index.
// 

$(document).ready(function () {

    // 
    // Change exams start and end time.
    // 
    $(document).on('click', '.change-time', function () {
        var container = $(this).closest('.list-group-item');
        var exam = $(container).attr('data-id');

        ajax(
                baseURL + 'ajax/core/invigilator/exam/read',
                {
                    'id': exam
                },
                function (status) {
                    if (status) {
                        var stime = status.starttime;
                        var etime = status.endtime;

                        var changer = $("#exam-datetime-changer");

                        changer.find("#exam-starttime").val(stime);
                        changer.find("#exam-endtime").val(etime);

                        changer.dialog({
                            autoOpen: true,
                            modal: true,
                            buttons: {
                                OK: function () {

                                    stime = changer.find("#exam-starttime").val();
                                    etime = changer.find("#exam-endtime").val();

                                    if (stime.length === 0) {
                                        stime = null;
                                    }
                                    if (etime.length === 0) {
                                        etime = null;
                                    }

                                    ajax(
                                            baseURL + 'ajax/core/invigilator/exam/update',
                                            {
                                                'id': exam,
                                                'starttime': stime,
                                                'endtime': etime
                                            }, function (status) {
                                        if (status) {
                                            changer.dialog('close');
                                        }
                                    });
                                },
                                Cancel: function () {
                                    changer.dialog('destroy');
                                }
                            },
                            close: function () {
                                changer.dialog('destroy');
                            }
                        });
                    }
                });
    });

    // 
    // Handle exam progress button click in exam archive.
    //
    $(document).on('click', '.exam-progress', function () {

        if ($(this).hasClass('creator') && $(this).hasClass('upcoming') || $(this).hasClass('running')) {
            var prompt = "This exam is not yet published. If you chose to publish it now, then it will \nshow up as an upcoming exam for student but can't be opened by them \nbefore the exam actually starts.\n\nDo you want to publish it?";
            var result = confirm(prompt);

            if (result) {
                ajax(
                        baseURL + 'ajax/core/creator/exam/update',
                        {
                            'id': $(this).attr('data-id'),
                            'published': 1
                        },
                        function (status) {
                            if (status) {
                                location.reload();
                            }
                        }
                );
            }
        }

        if ($(this).hasClass('creator') && $(this).hasClass('published')) {
            var prompt = "This exam has been published. If you chose to revoke the publishing, then it will \nno longer show up as an upcoming exam for students.\n\nDo you want to unpublish it?";
            var result = confirm(prompt);

            if (result) {
                ajax(
                        baseURL + 'ajax/core/creator/exam/update',
                        {
                            "id": $(this).attr('data-id'),
                            "published": 0
                        },
                        function (status) {
                            if (status) {
                                location.reload();
                            }
                        }
                );
            }
        }

        if ($(this).hasClass('decoder') && $(this).hasClass('corrected')) {
            var prompt = "All answers has been corrected on this exam. If you chose to continue and \ndecode the exam, then no more correction can be done.\n\nDo you want to continue and decode it?";
            var result = confirm(prompt);

            if (result) {
                ajax(
                        baseURL + 'ajax/core/decoder/exam/update',
                        {
                            "id": $(this).attr('data-id'),
                            "decoded": 1
                        },
                        function (status) {
                            if (status) {
                                location.reload();
                            }
                        }
                );
            }
        }

        return false;
    });

    // 
    // Toggle display of exam details.
    // 
    $(document).on('click', '.exam-state-show', function () {
        var exam = $(this).closest('li').attr('data-id');
        var target = $(this).closest('li').children('.exam-state-view').children('div');

        target.toggle();

        if (target.is(":visible")) {
            $.ajax({
                type: "GET",
                data: null,
                url: baseURL + 'exam/details/' + exam,
                success: function (response) {
                    target.html(response);
                }
            });
        }

        return false;
    });

    // 
    // Show student management dialog:
    // 
    $(document).on('click', '.manage-students', function () {
        $.ajax({
            type: "POST",
            data: {'exam_id': $(this).attr('data-id')},
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
                    }
                });
            }
        });
    });

    // 
    // Show exam check dialog:
    // 
    $(document).on('click', '.check-exam', function () {
        $.ajax({
            type: "POST",
            data: {
                'exam_id': $(this).attr('data-id'),
                'readonly': 1
            },
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

    // 
    // Show exam duplicate dialog:
    // 
    $(document).on('click', '.reuse-exam', function () {
        var examId = $(this).closest('.list-group-item').attr('data-id');
        var dialog = $("#reuse-exam-dialog").dialog({
            autoOpen: true,
            modal: true,
            buttons: {
                "Proceed further »": function () {

                    var data = {'options[]': []};
                    $('.exam-reuse-opt').filter(':checked').each(function () {
                        data['options[]'].push($(this).val());
                    });
                    $.ajax({
                        type: "POST",
                        data: data,
                        url: baseURL + 'exam/replicate/' + examId,
                        success: function (response) {
                            var resp = jQuery.parseJSON(response);
                            if (resp.status === 'success') {
                                location.href = baseURL + 'exam/update/' + resp.exam_id + '/creator';
                            } else {
                                alert(resp.message);
                            }
                        },
                        error: function (xhr) {
                            dialog.dialog("option", "buttons", {});
                            dialog.html(xhr.responseText);
                            dialog.show();
                        }
                    });
                },
                Cancel: function () {
                    $(this).dialog('destroy');
                }
            },
            close: function () {
                $(this).dialog('destroy');
            }
        });

    });

    // 
    // Prompt before delete exam:
    // 
    $(document).on('click', '.del-exam', function () {
        var examLine = $(this).closest('.list-group-item');
        var examId = $(examLine).attr('data-id');
        var examName = $(examLine).find('.exam-name').html();

        if (confirm("Are you sure you want to delete exam: '" + jQuery.trim(examName) + "'")) {
            ajax(
                    baseURL + 'ajax/core/creator/exam/delete',
                    {"id": examId},
                    function (examData) {
                        $(examLine).slideUp(500, function () {
                            $(this).remove();
                            location.reload();
                        });
                    },
                    "POST",
                    true,
                    false
                    );
        }
    });

    // 
    // Simple delay function:
    // 
    var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    // 
    // On search string input:
    // 
    $(document).on('keyup', '.exam-search-box', function (e) {
        data.search = $(this).val().trim();
        var element = $(this);

        delay(function () {
            if (element.val().length > 1 || element.val().length === 0) {

                data.first = 1;
                var source = element.closest('article').parent();

                showSectionIndex(source);
            }
        }, 500);
        return false;
    });

    $(document).on('click', '.search-exam', function () {
        // Show advanced search options.
    });

    // 
    // On order by field changed:
    // 
    $(document).on('change', '.exam-order-by', function () {
        var source = $(this).closest('article').parent();
        data.order = $(this).val();

        showSectionIndex(source);
        return false;
    });

    // 
    // On sort order changed:
    // 
    $(document).on('click', '.exam-sort-order', function () {
        if ($(this).hasClass('fa-arrow-circle-down')) {
            $(this).removeClass('fa-arrow-circle-down').addClass('fa-arrow-circle-up');
            $(this).attr('sort', 'asc');
        } else {
            $(this).removeClass('fa-arrow-circle-up').addClass('fa-arrow-circle-down');
            $(this).attr('sort', 'desc');
        }

        var source = $(this).closest('article').parent();
        data.sort = $(this).attr('sort');

        showSectionIndex(source);
        return false;
    });

    // 
    // On page index clicked:
    // 
    $(document).on('click', '.pagination > li', function () {

        $(this).closest('.pagination').find('li').removeClass('active');
        $(this).addClass('active');

        var source = $(this).closest('article').parent();
        data.first = Number($(this).attr('page'));

        showSectionIndex(source);
        return false;
    });

    // 
    // Fake object.assing for IE 11:
    // 
    if (Object.assign === undefined) {
        Object.prototype.assign = function (obj) {
            return $.extend({}, obj);
        };
    }

    // 
    // Set default section filtering options. Clone filtering object to prevent
    // modify be reference when setting properties in data object.
    // 
    var data = Object.assign({}, filter);

    // 
    // On accordion tab expanded:
    // 
    $('input[type="radio"]').on('click', function () {
        var parent = $(this).parent();
        var target = parent.find('.exam-listing-area');

        // 
        // Check if already initialized:
        // 
        if (target.children().length > 0) {
            return;
        }

        // 
        // Reset filter on section switch:
        // 
        data = Object.assign({}, filter);
        data.sect = parent.attr('section-role');

        // 
        // Show this section with delay for animation:
        // 
        delay(function () {
            showSectionIndex(parent);
        }, 200);
    });

    // 
    // Show exam index. The source parameter is the containing div.
    // 
    var showSectionIndex = function (source) {
        var target = source.find('.exam-listing-area');
        var role = source.attr('section-role');

        loadSectionIndex(target, role);
    };

    // 
    // Load exam listing.
    // 
    var loadSectionIndex = function (target, role) {
        // 
        // Send AJAX request:
        // 
        $.ajax({
            type: "POST",
            data: data,
            url: baseURL + 'exam/section/' + role,
            success: function (response) {
                try {
                    target.hide().html(response).fadeIn();
                } catch (Error) {
                    target.html(response);  // IE 11 fix
                }
            }
        });
    };

    // 
    // Load data in all expanded sections:
    // 
    if (expand.length > 0) {
        for (var i = 0; i < expand.length; ++i) {
            var source = '[section-role="' + expand[i] + '"]';
            var parent = $(document).find(source);

            showSectionIndex(parent);
        }
    }

});

