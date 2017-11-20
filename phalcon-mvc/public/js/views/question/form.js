/* global tabCounter, tabId, CKEDITOR, qIsEditable, baseURL, examId, qId */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    form.js
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

// 
// Question editing form.
// 

var qPartTabs = $("#qPartTabs").tabs();
var tabTemplate = "<li><a href='#{href}'>#{label}</a> <span class='ui-icon ui-icon-close'>Remove Tab</span></li>";
var libJs = '';

$('.lib_resources_list').sortable();

// 
// Initialize opentip for adding question correctors:
// 
$('.search-catalog-service').each(function (index, element) {
    attachCatalogSearch(index, element);
});

// ----------------------------------------
// Question parts management (Tabs)
// ----------------------------------------

// 
// Add new question part:
// 
var addQuestPartTab = function () {

    var qPartTitle = String.fromCharCode(96 + tabCounter);
    var label = "Part " + qPartTitle,
            id = "q-part-" + tabId,
            li = $(tabTemplate.replace(/#\{href\}/g, "#" + id).replace(/#\{label\}/g, label)),
            tabContentHtml = qPartTabs.find("#q-parts-wrapper > .ui-tabs-panel")
            .filter(':first')
            .html()
            .replace('checked="checked"', "");  // Clear selected answer type

    qPartTabs.find(".ui-tabs-nav").append(li);
    qPartTabs.find("#q-parts-wrapper").append("<div id='" + id + "' class='q-part'>" + tabContentHtml + "</div>");

    // 
    // Refresh plugins on newly added content:
    // 
    qPartTabs.tabs("refresh");
    qPartTabs.tabs({active: tabCounter - 1});
    $(".accordion").accordion({heightStyle: "content"});

    if (tabCounter === 2) {
        qPartTabs.find("#q-parts-wrapper > .ui-tabs-panel").css('padding', '1em 1.4em');
        $('#q-part-tabs').show(200);
    }
    tabId++;
    tabCounter++;

    //
    // Enable CKEditor:
    // 
    $('#' + id).find('.write_q_ckeditor').attr('id', 'q_text' + tabId).val('');
    $('#' + id).find('.ans_type').hide();
    $('#' + id).find('.lib_resources_list').empty();
    $('#' + id).find('#cke_q_text1').remove();
    CKEDITOR.replace('q_text' + tabId, {
        height: '100px'
    });

    // 
    // Inline ckeditor on elements with ckeditor="choice" attribute:
    // 
    $('#' + id).find('[ckeditor="choice"]').each(function (index, element) {
        CKEDITOR.inline(element);
    });
};

/**
 * Events binding area.
 */
$(document).ready(function () {

    // 
    // Accordion:
    // 
    $(".accordion").accordion({
        heightStyle: "content"
    });

    // 
    // CKEditors:
    // 
    for (var i = 1; i < tabCounter; i++) {
        CKEDITOR.replace('q_text' + i, {
            height: '100px'
        });

    }
    CKEDITOR.config.autoParagraph = false;

    // 
    // Prepare correctors list:
    // 
    var tmp = '<option value="">Choose a corrector for question</option>';
    $('.left-col-user').each(function (index, element) {
        if ($(element).html().replace(/\s/g, '') !== '' && tmp.replace(/\s/g, '').indexOf($(element).html().replace(/\s/g, '')) < 0) {
            tmp += '<option value="' + $(element).attr('data-user') + '">' + $(element).html() + '</option>';
        }
    });

    setTimeout(function () {
        if (!qIsEditable) {
            $("#question-form-dialog-wrap").dialog("option", "buttons",
                    [
                        {
                            text: "Cancel",
                            click: function () {
                                closeToolTips();
                                $(this).dialog('destroy');
                            }
                        }
                    ]
                    );
        }
    }, 500);

    // 
    // Removing the tab on click:
    // 
    qPartTabs.delegate("span.ui-icon-close", "click", function () {
        var panelId = $(this).closest("li").remove().attr("aria-controls");
        $("#" + panelId).remove();
        qPartTabs.tabs("refresh");

        qPartTabs.find(".ui-tabs-nav > li").each(function (index, element) {
            $(element).find('a').html("Part " + String.fromCharCode(96 + (index + 1)));
        });
        tabCounter--;
        if (tabCounter === 2) {
            qPartTabs.find("#q-parts-wrapper > .ui-tabs-panel").css('padding', '0px');
            $('#q-part-tabs').hide(200);
        }
    });

    if (oe_module_loaded("question-form")) {
        return;
    }

    // 
    // Answer type selector (single input, textarea, drawingarea, ...
    // 
    $('body').on('change', '.ans_type_selector', function () {
        $(this).closest('.q-part').find('.ans_type').hide();
        $(this).closest('.ans_type_selector_box_wrap').parent().find('.ans_type_selector').prop('checked', false);
        $(this).prop('checked', true);
        $(this).closest('.ans_type_selector_box_wrap').find('.ans_type').show();
    });

    // 
    // Add resources to the question from media library dialog:
    // 
    $('body').on('click', '.add_media', function () {
        $.ajax({
            url: baseURL + 'utility/media/library',
            data: {'exam_id': examId},
            success: function (data) {
                $("#media-library").html(data);
                $("#media-library").dialog({
                    autoOpen: true,
                    width: "75%",
                    height: 650,
                    modal: true,
                    buttons: {
                        OK: function () {
                            $('#lib-selected-list > .lib-item').each(function (index, item) {
                                // 
                                // Get name and link from selected item:
                                // 
                                var name = $(item).find('.text').text().trim();
                                var path = $(item).find('.select-resource').attr('href');

                                // 
                                // Prefix URL if relative:
                                // 
                                if (path[0] !== '/') {
                                    path = baseURL + path;
                                }

                                // 
                                // Append selected resources to list in question editor:
                                // 
                                $('#' + $(".ui-tabs-active").attr("aria-controls"))
                                        .find('.lib_resources_list')
                                        .append('\
                                                    <li>\
                                                            <i class="fa fa-close resource-item-remove"></i>\
                                                            <i class="fa fa-pencil resource-item-edit"></i>\
                                                            <a href="' + path + '" file-path="' + path + '" target="_blank">' + name + '</a>\
                                                    </li>'
                                                );
                            });
                            $(this).dialog('destroy');
                        },
                        Cancel: function () {
                            closeToolTips();
                            $(this).dialog('destroy');
                        }
                    },
                    close: function () {
                        closeToolTips();
                    }
                });
            }
        });
        return false;
    });

    $('body').on('click', '.set_canvas_background', function () {
        $.ajax({
            url: baseURL + 'utility/media/library',
            data: {'exam_id': examId},
            success: function (data) {
                $("#media-library").html(data);

                $("#media-library").find("#audio-tab").parent().find("ul > li > a[href='#audio-tab']").hide();
                $("#media-library").find("#video-tab").parent().find("ul > li > a[href='#video-tab']").hide();
                $("#media-library").find("#other-tab").parent().find("ul > li > a[href='#other-tab']").hide();

                $("#media-library").dialog({
                    autoOpen: true,
                    width: "55%",
                    modal: true,
                    buttons: {
                        OK: function () {
                            $('#lib-selected-list > .lib-item').each(function (index, item) {
                                // 
                                // Get name and link from selected item:
                                // 
                                var name = $(item).find('.text').text().trim();
                                var path = $(item).find('.select-resource').attr('href');

                                // 
                                // Prefix URL if relative:
                                // 
                                if (path[0] !== '/') {
                                    path = baseURL + path;
                                }

                                $('#' + $(".ui-tabs-active").attr("aria-controls"))
                                        .find('.lib_canvas_background')
                                        .empty()
                                        .append('\
                                                    <li>\
                                                            <i class="fa fa-close resource-item-remove"></i>\
                                                            <i class="fa fa-pencil resource-item-edit"></i>\
                                                            <a href="' + path + '" file-path="' + path + '" target="_blank">' + name + '</a>\
                                                    </li>'
                                                );
                            });
                            $(this).dialog('destroy');
                        },
                        Cancel: function () {
                            closeToolTips();
                            $(this).dialog('destroy');
                        }
                    },
                    close: function () {
                        closeToolTips();
                    }
                });
            }
        });
        return false;
    });

    // 
    // Add or delele new sortable option in option type of questions:
    // 
    $('body').on('click', '.add-new-sortable', function () {
        var instances = $(document).find('[ckeditor="choice"]').length;
        var editor = 'editor' + instances;

        $(this).closest('.choice_q').find('.question_opts').append(
                '<div style="padding-top:5px"> \
                    <span class="delopt hideable"> \
                        <i class="fa fa-minus-circle" aria-hidden="true" style="color: red"></i> \
                    </span>\n\
                    <input type="checkbox"> \
                    <div class="editabletext" ckeditor="choice" contenteditable="true" style="display: inline" id="' + editor + '">Option - click to edit</div> \
                </div>');
        CKEDITOR.inline(editor);
    });

    $('body').on('click', '.delopt', function () {
        $(this).parent().slideUp(500, function () {
            $(this).remove();
        });
    });

    // 
    // Remove this library resource:
    // 
    $('body').on('click', ".resource-item-remove", function () {
        $(this).parent().remove();
    });

    // 
    // Edit name on this library resource:
    // 
    $('body').on('click', ".resource-item-edit", function () {
        if ($(this).parent().find('a').attr("contenteditable") === undefined) {
            $(this).parent().find('a').attr("contenteditable", true);
            $(this).parent().find('a').focus();
        } else {
            $(this).parent().find('a').removeAttr("contenteditable");
        }
    });

    // 
    // On corrector delete:
    // 
    $(document).on('click', '.del-corrector', function () {

        var delCorrector = $(this);
        if ($('.q_corrector_list').find('li:visible').length > 1) {

            if (qId) {
                // 
                // Send AJAX request to delete selected corrector:
                // 
                ajax(
                        baseURL + 'ajax/core/creator/corrector/delete',
                        {"id": $(delCorrector).parent().find('span').attr('data-rec')},
                        function (status) {
                            $(delCorrector).parent().slideUp(500, function () {
                                $(this).remove();
                            });
                        }
                );
            } else {
                $(this).parent().slideUp(500, function () {
                    $(this).remove();
                });
            }
        } else {
            alert("A question must have at least one corrector");
        }
    });

});
