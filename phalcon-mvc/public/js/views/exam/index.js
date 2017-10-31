// JavaScript Document specific to exam/index
// @Author Ahsan Shahzad [MedfarmDoIT]

/*-- var initialization --*/
var stEvents = '';

/*-- Event handlers --*/
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
        console.log($(this));
        var exam = $(this).attr('data-id');
        console.log(exam);
        console.log($(this).hasClass('running'));

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

    $('.check-exam').click(function () {
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
                            if (resp.status == 'success') {
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

    $(document).on('click', '.del-exam', function () {
        var examLine = $(this).closest('.list-group-item');
        var examId = $(examLine).attr('data-id');
        var examName = $(examLine).find('.exam-name').html();

        if (confirm("Are you sure you want to delete this Exam: '" + jQuery.trim(examName) + "'")) {
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

    $(document).on('keyup', '.exam-search-box', function (e) {
        if ($(this).val() == '') {
            var examListingAreas = $(this).closest('.exam-listing-wrapper').find('.exam-listing-area');
            if (examListingAreas.length > 1) {
                $(examListingAreas).not(':last').remove();
            }
        } else if (e.which == 13) {
            $(this).parent().find('.search-exam').trigger('click');
        }
    });

    $(document).on('click', '.search-exam', function () {
        reloadExamList($(this), 0);
    });

    $(document).on('change', '.exam-sort-by', function () {
        reloadExamList($(this));
    });

    $(document).on('click', '.exam-sort-order', function () {
        if ($(this).hasClass('fa-arrow-circle-down')) {
            $(this).removeClass('fa-arrow-circle-down').addClass('fa-arrow-circle-up');
            $(this).attr('order', 'asc');
        } else {
            $(this).removeClass('fa-arrow-circle-up').addClass('fa-arrow-circle-down');
            $(this).attr('order', 'desc');
        }

        reloadExamList($(this));
    });

    $(document).on('click', '.pagination > li', function () {

        $(this).closest('.pagination').find('li').removeClass('active');
        $(this).addClass('active');

        reloadExamList($(this));

        return false;
    });

    var reloadExamList = function (element, offset) {
        // section
        var section = $(element).closest('.exam-listing-wrapper');
        var role = $(section).attr('exam-role');
        var examSortBy = $(section).find('.exam-sort-by').val();
        var examSortOrder = $(section).find('.exam-sort-order').attr('order');
        var searchKey = $(section).find('.exam-search-box').val();
        if (searchKey) {
            var cond = ["name like :key: or code like :key:", {"key": "%" + searchKey + "%"}];
        } else {
            var cond = [];
        }

        if (typeof offset === 'undefined') {
            offset = $(section).find('.pagination > .active').attr('offset');
        }
        //"flags":["upcoming"]
        // prepare data
        data = {"params": {
                "role": role,
                "conditions": [cond],
                "order": examSortBy + " " + examSortOrder,
                "limit": offset ? examPerPage : 100000,
                "offset": offset
            }
        };

        // send ajax request	
        ajax(
                baseURL + 'ajax/core/' + role + '/exam/read',
                data,
                function (examData) {
                    if (examData.length) {
                        //alert(JSON.stringify(examData));
                        populateExamGrid(examData, section, cond.length);
                    } else {
                        alert("No such exam found!");
                    }
                }
        );
    };

    var populateExamGrid = function (examData, section, populateInSearchGrid) {

        var populatePages = false;
        var examRole = $(section).attr('exam-role') != $(section).attr('section-role') ? $(section).attr('section-role') : $(section).attr('exam-role');

        // grid that appears when someone searches for exam
        if (populateInSearchGrid) {
            if ($(section).find('.exam-listing-area').length > 1) {
                var examListingArea = $(section).find('.exam-listing-area').first();
            } else {
                var examListingArea = $(section).find('.exam-listing-area').clone();
                populatePages = true;
            }
        } else {
            var examListingArea = $(section).find('.exam-listing-area');
        }
        $(examListingArea).find('.exam-list').find('li').not(':first').not(':first').remove();
        $(examListingArea).find('.exam-progress').hide();   // TODO: display exam progress.

        $.each(examData, function (i, exam) {
            var start = exam.starttime ? exam.starttime.split(" ") : ["0000:00:00", "00:00"];
            var ends = exam.endtime ? exam.endtime.split(" ") : ["0000:00:00", "00:00"];
            var examName = exam.name == '' || exam.name == ' ' ? 'Untitled exam' : exam.name;
            var examItem = $(examListingArea).find('.exam-list').find('li').not(':first').first().clone();
            $(examItem).attr('data-id', exam.id);
            $(examItem).find('.exam-name').html(examName + (exam.code != '' && exam.code != null ? " (" + exam.code + ")" : ""));
            if (exam.starttime) {
                $(examItem).find('.exam-date-time').show();
                $(examItem).find('.exam-date').html(start[0]);
                $(examItem).find('.exam-starts').html(start[1]);
                $(examItem).find('.exam-ends').html(ends[1]);
            } else {
                $(examItem).find('.exam-date-time').hide();
            }

            $(examItem).find('.published-exam').hide();
            $(examItem).find('.draft-exam').hide();
            $(examItem).find('.upcoming-exam').hide();

            if (exam.state & 0x4000) {
                $(examItem).find('.draft-exam').hide();
                $(examItem).find('.upcoming-exam').hide();
                $(examItem).find('.published-exam').show();
                if (examRole == 'creator') {
                    $(examItem).css('background-color', '#ffffed');
                }
            } else if (exam.state & 0x2000) {
                $(examItem).find('.draft-exam').show();
                $(examItem).find('.upcoming-exam').hide();
                $(examItem).find('.published-exam').hide();
                $(examItem).css('background-color', '#fff');
            } else {
                $(examItem).find('.draft-exam').hide();
                $(examItem).find('.upcoming-exam').show();
                $(examItem).find('.published-exam').hide();
                $(examItem).css('background-color', '#fff');
            }

            //list operational buttons as per the exam role and status
            $(examItem).find('.exam-show-options').empty();
            $.each(examSections[examRole]["show-options"], function (btnKey, btnProp) {

                var showBtn = false;
                if (btnProp["show-on-flags"] == '*') {
                    showBtn = true;
                } else {
                    $.each(btnProp["show-on-flags"], function (i, flag) {
                        if (exam.flags.indexOf(flag) >= 0) {
                            showBtn = true;
                            return false;
                        }
                    });
                }

                if (showBtn) {
                    var target = btnProp["target"].indexOf('/') >= 0 ? baseURL + (btnProp["target"].replace("{exam-id}", exam.id)) : '#';
                    var btnClass = btnProp["target"].indexOf('/') >= 0 ? "" : btnProp["target"] + " prevent";
                    $(examItem)
                            .find('.exam-show-options')
                            .append('<a class="' + btnClass + '" href="' + target + '" data-id="' + exam.id + '">' + $('#' + btnKey).html() + '</a>');
                }
            })

            $(examListingArea).find('.exam-list').append(examItem);

            if (i == examPerPage - 1) {
                return false;
            }
        });

        if (populatePages) {
            var totalPgs = Math.ceil(examData.length / examPerPage);
            var pagination = $(examListingArea).find('.pagination');
            $(pagination).find('li').not(':first').remove();
            for (var i = 1; i <= totalPgs; i++) {
                var pageItem = $(pagination).find('li').first().removeClass('active').clone();
                $(pageItem).find('a').html(i);
                $(pageItem).attr('offset', ((i - 1) * examPerPage));
                if (i == 1) {
                    $(pageItem).addClass('active');
                }
                $(pagination).append(pageItem);
            }
            $(pagination).find('li').first().remove();
        }

        $(examListingArea).find('.exam-list').find('li').eq(1).remove();
        $(section).find('.exam-listing-area').parent().prepend(examListingArea);
    };

});

