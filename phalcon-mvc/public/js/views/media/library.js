/* global baseURL, examId, user */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    library.js
// Created: 2015-02-17 02:22:48
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// Author:  Anders Lövgren (QNET)
// 

// 
// The media files library.
// 

$(function () {
    'use strict';

    // 
    // Initialize tabs:
    // 
    $('#media-types').tabs();

    // 
    // Enable sort of selected resources:
    // 
    $("#lib-selected-list").sortable();

    // 
    // Handler for simple file upload:
    // 
    $('#fileupload').fileupload({
        url: baseURL + 'utility/media/upload',
        dataType: 'json',
        done: function (e, data) {
            $('#lib-default-msg').hide();
            $.each(data.result.files, function (index, file) {
                if (typeof file.url !== 'undefined') {
                    onUploadComplete(file);
                } else {
                    alert("Unable to upload file '" + file.name + "': " + file.error);
                }
            });
        },
        error: function (err) {
            alert(err.responseText + " (" + err.statusText + ")");
        }
    }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');

    // 
    // On upload complete. Prompt user if file should be added to media library
    // in addition to be added inside selected files list.
    // 
    var onUploadComplete = function (file) {

        if (file.src === undefined) {
            file.src = baseURL + 'img/file-icon.png';
        }
        if (file.mime === undefined) {
            file.mime = file.type.split('/');
        }

        if (file.tab === undefined) {
            switch (file.mime[0]) {
                case 'image':
                case 'video':
                case 'audio':
                    file.tab = file.mime[0];
                    break;
                default:
                    file.tab = 'other';
                    break;
            }
        }

        if (file.tab === 'image') {
            file.src = file.url;
        }

        var question = "File '" + file.name + "' was successful uploaded. \n\r Do you want to save this file in the media file library for future use? ";

        if (confirm(question) === false) {
            insertResourceFile(file);
        } else {
            createResourceFile(file);
        }
    };

    // 
    // Add template to media library.
    // 
    var addToMediaLibrary = function (template, tab) {
        $("#" + tab + '-tab').append(template);
        $("#" + tab + '-tab').trigger('click');

        template.show().addClass("selected");
    };

    // 
    // Add template to selected files.
    // 
    var addToSelectedFiles = function (template) {
        $("#lib-selected-list").append(template);
        $("#lib-selected-list").sortable();

        template.find('.tools').hide();
        template.find('.text').attr("contenteditable", "true");

        template.show().addClass("appended");
    };

    // 
    // Insert resource file.
    // 
    var insertResourceFile = function (file) {
        var template = cloneItemTemplate(0, file, 'exam');
        addToSelectedFiles(template);
    };

    // 
    // Create resource file.
    // 
    var createResourceFile = function (file, callback) {
        // 
        // Send AJAX request to insert this upload in resource table:
        // 
        ajax(
                baseURL + 'ajax/core/contributor/resource/create',
                {
                    exam_id: examId,
                    name: file.name,
                    path: file.url,
                    type: file.mime[0],
                    subtype: file.mime[1],
                    user: user
                },
                function (data) {
                    var template1 = cloneItemTemplate(data.id, file, data.shared);
                    var template2 = template1.clone();

                    addToMediaLibrary(template1, file.tab);
                    addToSelectedFiles(template2);
                }
        );
    };

    // 
    // Clone item template and initialize.
    // 
    var cloneItemTemplate = function (id, file, share) {
        var template = $("#lib-item-template > .lib-item").clone();

        template.attr('media-id', id);
        template.find('.select-resource').attr('href', file.url);
        template.find('.lib-resource-image').attr('src', file.src);
        template.find('.text').text(file.name);
        template.find('.lib-item-share').attr('item-share', share);
        
        return template;
    };

    // 
    // Called when resource in media library was selected.
    // 
    var selectResourceFile = function (resource) {
        addToSelectedFiles(resource.clone());
        $("#lib-default-msg").hide();   // At least one now
    };

    // 
    // Show personal library items.
    // 
    var showPersonalItems = function () {
        $('.lib-personal').toggle();
    };

    // 
    // Toggle expanded mode on library items.
    // 
    var showExpandedItems = function () {
        var expand = $(".lib-item").first().hasClass("expanded") === false;

        if (expand) {
            $(".lib-item").addClass("expanded");
        } else {
            $(".lib-item").removeClass("expanded");
        }
    };

    // 
    // Delete library item.
    // 
    var deleteItem = function (source) {
        var item = source.closest('.lib-item');
        var name = item.find('.text').text().trim();

        if (item.attr('media-id') === 0) {
            return;
        }
        if (confirm('Are you sure you want to delete resource ' + name + '?') === false) {
            return;
        }

        // 
        // Send AJAX request to save data:
        // 
        ajax(
                baseURL + 'ajax/core/contributor/resource/delete',
                {
                    id: item.attr('media-id')
                },
                function (data) {
                    item.remove();
                }
        );
    };

    // 
    // Edit resource name.
    // 
    var editItemName = function (source) {
        // 
        // Get values from library item:
        // 
        var item = source.closest('.lib-item');
        var name = item.find('.text').text().trim();

        // 
        // Prepare HTML template:
        // 
        var template = getEditItemTemplate(item);
        template.find("#lib-item-name").show();
        template.find(".update-lib-item-name")
                .attr("value", name)
                .prop("readonly", false);

        // 
        // Open tooltip dialog:
        // 
        var opentip = new Opentip(source, template.html(),
                {
                    style: "drops",
                    tipJoint: "top left"
                }
        );
        opentip.show();
    };

    // 
    // Edit resource sharing.
    // 
    var editItemShare = function (source) {
        // 
        // Get values from library item:
        // 
        var item = source.closest('.lib-item');
        var type = item.find('.lib-item-share').attr('item-share');

        // 
        // Prepare HTML template:
        // 
        var template = getEditItemTemplate(item);
        template.find("#lib-item-share").show();
        template.find(".update-lib-item-shared > option").each(function (index, element) {
            if ($(element).attr('value') === type) {
                $(element).attr('selected', true);
            }
        });

        // 
        // Open tooltip dialog:
        // 
        var opentip = new Opentip(source, template.html(),
                {
                    style: "drops",
                    tipJoint: "top left"
                }
        );
        opentip.show();
    };

    // 
    // Get HTML template for edit library items:
    // 
    var getEditItemTemplate = function (item) {
        var template = $("#lib-item-edit").clone();

        template.show();
        template.find('.update-lib-item-details').attr('media-id', item.attr('media-id'));

        template.off('focus');

        return template;
    };

    // 
    // Update library item name.
    // 
    var saveItemName = function (id, value) {
        // 
        // Send AJAX request to save data:
        // 
        ajax(
                baseURL + 'ajax/core/contributor/resource/update',
                {
                    id: id,
                    name: value
                },
                function (data) {
                }
        );
    };

    // 
    // Update library item share.
    // 
    var saveItemShare = function (id, value) {
        // 
        // Send AJAX request to save data:
        // 
        ajax(
                baseURL + 'ajax/core/contributor/resource/update',
                {
                    id: id,
                    shared: value
                },
                function (data) {
                }
        );
    };

    // 
    // Called to save item editor values.
    // 
    var saveItemDetails = function (sender) {
        var id = sender.attr('media-id');
        var editor = sender.parent();

        if (id !== 0) {
            var element = editor.find('.lib-item-save:visible');

            if (element.attr('id') === 'lib-item-name') {
                saveItemName(id, element.find('input').val());
            }
            if (element.attr('id') === 'lib-item-share') {
                saveItemShare(id, element.find('select option:selected').val());
            }
        }
    };

    if (oe_module_loaded("media-library")) {
        return;
    }

    $(document).on('focus', '.update-lib-item-name', function () {
        $(this).focus();    // Overflow by purpose
        return false;
    });

    $(document).on('click', '.update-lib-item-details', function () {
        saveItemDetails($(this));
        return false;
    });

    $(document).on('click', '.select-resource', function () {
        var item = $(this).closest('.lib-item');

        if (item.hasClass('appended') !== false) {
            return false;
        }
        if (item.hasClass('selected') === false) {
            selectResourceFile(item);
        }

        return false;
    });

    $(document).on('click', '#lib-item-personal', function () {
        showPersonalItems();
        return false;
    });

    $(document).on('click', '#lib-item-expand', function () {
        showExpandedItems();
        return false;
    });

    $(document).on('click', '.lib-item-edit', function () {
        editItemName($(this));
        return false;
    });

    $(document).on('click', '.lib-item-del', function () {
        deleteItem($(this));
        return false;
    });

    $(document).on('click', '.lib-item-share', function () {
        editItemShare($(this));
        return false;
    });

});
