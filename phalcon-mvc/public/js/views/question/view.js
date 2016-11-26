// JavaScript Document specific to View question
// @Author Ahsan Shahzad [MedfarmDoIT]

var dirtybit = 0;
var syncEvery = 10000; // 10 seconds 
var ansJson = {};
var totalSyncs = 0;
var canvasData = [];
var canvasElem = [];

// 
// Schedule synchronize of answer.
// 
setInterval(function () {
    if (dirtybit) {
        dirtybit = 0;
        syncAnswers(true);
    }
}, syncEvery);

// 
// Synchronize answers to server side.
// 
function syncAnswers(async, redirectToAfterSync)
{
    redirectToAfterSync = typeof redirectToAfterSync !== 'undefined' ? redirectToAfterSync : false;
    if (redirectToAfterSync && !dirtybit) {
        location.href = redirectToAfterSync;
        return;
    }

    try {
        var failed = false;
        if (ansId === 'None!') {
            if (!async) {
                return;
            } else {
                if (redirectToAfterSync) {
                    location.href = redirectToAfterSync;
                }
            }
        }
        $('.q-part').each(function (index, qPart) {
            var qPartName = $(qPart).attr('data-id');
            var ansType = $(qPart).find('.q-part-ans-type').attr('data-id');
            var answers = $(qPart).find('.q-part-ans-type').find('.changeable');

            // 
            // Initialize json obj attr
            // 
            ansJson[qPartName] = {};
            ansJson[qPartName]["type"] = ansType;
            ansJson[qPartName]["ans"] = [];

            // 
            // Make JSON as per ansType
            // 
            if (ansType == 'textbox') {
                console.log("saving text box data");

                ansData = $(answers).val();
                if (ansData.trim()) {
                    console.log("not empty:" + ansData);
                    ansJson[qPartName]["ans"].push(ansData);
                    $('#ansBkp' + qPartName).html(ansData);
                } else {
                    console.log("its empty .. so sending old value:" + $('#ansBkp' + qPartName).html());
                    ansJson[qPartName]["ans"].push($('#ansBkp' + qPartName).html());
                }
            } else if (ansType == 'textarea') {
                console.log("saving text area data");
                ansData = CKEDITOR.instances[$(answers).attr('id')].getData();

                if (ansData.trim()) {
                    console.log("not empty:" + ansData);
                    ansJson[qPartName]["ans"].push(ansData);
                    $('#ansBkp' + qPartName).html(ansData);
                } else {
                    console.log("its empty .. so sending old value:" + $('#ansBkp' + qPartName).html());
                    ansJson[qPartName]["ans"].push($('#ansBkp' + qPartName).html());
                }
            } else if (ansType == 'choicebox') {
                $(answers).each(function (index, opt) {
                    if ($(opt).is(':checked')) {
                        ansJson[qPartName]["ans"].push($(opt).val());
                    }
                });

            } else if (ansType == 'canvas') {
                var canvasId = $(answers).attr('id');
                var canvasJson = canvasData[canvasId];

                if (typeof (canvasElem[canvasId]) != 'undefined' && canvasElem[canvasId].getImage()) {

                    var canvasUrl = canvasElem[canvasId].getImage().toDataURL();
                    ansJson[qPartName]["ans"].push({"canvasJson": canvasJson, "canvasUrl": canvasUrl});

                }
            } else {
                ansData = CKEDITOR.instances[$(answers).attr('id')].getData();
                ansJson[qPartName]["ans"].push(ansData);
            }

        });

        ansJson["highlight-q"] = $('#highlight_q').is(':checked') ? 'yes' : 'no';

        $('.ans-sync-msg').show();

        failMsg = "Failed to save your answer! \r\n \
				Please report this issue to the exam invigilator immediately. \r\n \
				Please DO NOT refresh/close this web page otherwise we may loose your answer.\r\n";

        $.ajax({
            type: "POST",
            url: baseURL + 'core/ajax/student/answer/update',
            data: {"id": ansId, "answer": JSON.stringify(ansJson), "answered": 1},
            async: async,
            dataType: "json",
            timeout: 5000,
            error: function (x, t, m) {
                if (t === "timeout") {
                    alert("Seems like you lost your internet connection. Please make sure that internet cable is properly connected with computer. \r\n");
                } else {
                    if (m != '' && m != null) {
                        alert("Error occured! System was'nt able to save your answer during last 10 seconds. Please ignore if you see this message for the first time and review you answer again. Otherwise, please inform it to invigilator." + "\r\n\r\n >>>" + JSON.stringify(x) + "--" + t + "--" + m);
                    }
                }
            }
        }).done(function (respJson) {
            if (typeof respJson.success == "undefined") {
                if (async) {
                    alert(failMsg + JSON.stringify(respJson));
                } else {
                    return failMsg;
                }
            } else {
                totalSyncs++;
                $('.ans-sync-msg').hide();
                if (redirectToAfterSync) {
                    location.href = redirectToAfterSync;
                }
            }
        });
    } catch (err) {
        console.log(err);
        alert("Something went wrong in system. Please don't refresh/close web page window and contact your invigilator immediately." + JSON.stringify(err));
    }
}

// 
// Initialize canvas (the drawing area).
// 
var initCanvas = function (elementId, canvasJson) {
    $('#' + elementId).literallycanvas({
        onInit: function (lc) {
            canvasElem[elementId] = lc;

            if (localStorage.getItem(elementId) == canvasJson) {
                if (localStorage.getItem(elementId)) {
                    lc.loadSnapshotJSON(localStorage.getItem(elementId));
                    canvasData[elementId] = localStorage.getItem(elementId);
                }
            } else {
                if (localStorage.getItem(elementId) === null) {
                    lc.loadSnapshotJSON(canvasJson);
                    canvasData[elementId] = canvasJson;
                } else {
                    dirtybit = 1;
                    lc.loadSnapshotJSON(localStorage.getItem(elementId));
                    canvasData[elementId] = localStorage.getItem(elementId);
                }
            }

            lc.on('drawingChange', function () {
                dirtybit = 1;
                canvasData[elementId] = lc.getSnapshotJSON();
                localStorage.setItem(elementId, lc.getSnapshotJSON());
            })
        },
        defaultStrokeWidth: 2,
        secondaryColor: 'transparent',
        imageURLPrefix: baseURL + 'plugins/canvas/img',
        imageSize: {width: null, height: null}
    });
}

// 
// Event binding area.
// 
$(function () {

    $('#highlight_q').on('click', function () {
        if ($(this).is(':checked')) {
            $('#q' + qName + '_short > a').css('background-color', '#FEFF99'); /*#FBC676*/
        } else {
            $('#q' + qName + '_short > a').css('background-color', '#FEFEFE');
        }
    });

    $(document).on('change', '.changeable', function () {
        dirtybit = 1;
    });

    $(document).on('click', '.logout-me', function () {
        if (confirm("Are you sure you want to logout from OpenExam?")) {
            syncAnswers(true, $(this).attr('hlink'));
        }
        return false;
    });

    $(document).on('click', '.sync-answer', function () {
        location.href = $(this).attr('hlink');
        return false;
    });

    CKEDITOR.config.removeButtons = 'Link,Unlink';
    $('.ckeditor').each(function (index, element) {
        var limit = element.getAttribute('word-count-limit');
        if (limit === "") {
            limit = -1;
        }

        var spell = element.getAttribute('native-spell-check');
        if (spell === "") {
            spell = false;
        }

        var editor = CKEDITOR.replace(element.id, {
            height: '100px',
            disableNativeSpellChecker: spell === false,
            wordcount: {
                countHTML: false,
                showWordCount: true,
                showCharCount: false,
                maxWordCount: limit
            }
        });

        if (spell) {
            CKEDITOR.dialog.add('nativespellcheck', function (api) {
                // CKEDITOR.dialog.definition
                var dialogDefinition = {
                    title: 'Native Spell Check',
                    minWidth: 390,
                    minHeight: 130,
                    contents: [
                        {
                            id: 'tab1',
                            label: 'Label',
                            title: 'Title',
                            expand: false,
                            resizable: CKEDITOR.DIALOG_RESIZE_NONE,
                            padding: 0,
                            elements: [
                                {
                                    type: 'html',
                                    html: '\
<p>\n\
This addon uses the native spell check in the browser. Use <i>&lt;ctrl&gt; + &lt;right click&gt;</i> to <br/>\n\
access browser dictionaries and spelling suggestions.\n\
</p>\n\
<br/>\n\
<p>\n\
Click OK to toggle spell check as you type on/off for this text area.\n\
</p>'
                                }
                            ]
                        }
                    ],
                    buttons: [CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton],
                    onFocus: function () {
                        this.getContentElement('tab1').focus();
                    },
                    onOk: function () {
                        var edt = this.getParentEditor();

                        // 
                        // Get editor content node (not the textarea):
                        // 
                        var body = edt.document.getElementsByTag('body').getItem(0);
                        var enabled = false;

                        // 
                        // Toogle spell check on/off:
                        // 
                        if (body.hasAttribute('spellcheck') === 'false') {
                            enabled = false;
                        } else if (body.getAttribute('spellcheck') === 'true') {
                            enabled = true;
                        } else {
                            enabled = false;
                        }

                        body.setAttribute('spellcheck', !enabled);
                    }
                };

                return dialogDefinition;
            });

            editor.addCommand("nativespellcheck", new CKEDITOR.dialogCommand('nativespellcheck'));
            editor.ui.addButton('SuperButton', {
                label: "Spell check",
                toolbar: "editing",
                command: 'nativespellcheck',
                icon: 'spellchecker'
            });
        }
    });

    for (var i in CKEDITOR.instances) {
        CKEDITOR.instances[i].on('change', function () {
            dirtybit = 1;
        });
    }

    $(window).bind('beforeunload', function (event) {
        return syncAnswers(false);
    });

    $('.img-zoom').elevateZoom({
        responsive: true,
        zoomType: "window",
        zoomWindowPosition: 10,
        borderColour: "#dedede",
        cursor: "crosshair",
        scrollZoom: true,
        cursor:"pointer",
                zoomWindowFadeIn: 500,
        zoomWindowFadeOut: 750
    });

    $('.img-zoom-inner').elevateZoom({
        responsive: true,
        zoomType: "window",
        zoomWindowPosition: 2,
        borderColour: "#dedede",
        cursor: "crosshair",
        scrollZoom: true,
        cursor:"pointer",
                zoomWindowFadeIn: 500,
        zoomWindowFadeOut: 750
    });

    $(document).on('click', '.zoom-in, .zoom-out', function () {
        return false;
    });

    // 
    // Media plugin related settings and initializations:
    // 
    $.fn.media.defaults.flvPlayer = baseURL + 'swf/mediaplayer.swf';
    $.fn.media.defaults.mp3Player = baseURL + 'swf/mediaplayer.swf';
    $('a.media').media();

    // 
    // Handle question menu show/hide:
    // 
    if ($.cookie('qs-menu')) {
        $(document).trigger($.cookie('qs-menu'));
    }
});
