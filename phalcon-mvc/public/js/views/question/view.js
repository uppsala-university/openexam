// JavaScript Document specific to View question
// @author Ahsan Shahzad [MedfarmDoIT]
// @author Anders Lövgren BMC-IT

var dirtybit = 0;
var syncEvery = 10000; // 10 seconds 
var ansJson = {};
var totalSyncs = 0;
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
    if (ansId === 'None!') {
        return;     // Running in test case mode.
    }

    try {
        $('.q-part').each(function (index, qPart) {
            var qPartName = $(qPart).attr('data-id');
            var ansType = $(qPart).find('.q-part-ans-type').attr('data-id');
            var answers = $(qPart).find('.q-part-ans-type').find('.changeable');

            // 
            // The answer type should be explicit set, but assume it's a textarea if missing:
            // 
            if (ansType === undefined || ansType.length === 0) {
                ansType = "textarea";
                console.log("empty answer type (assuming textarea)")
            }

            // 
            // Initialize json obj attr
            // 
            ansJson[qPartName] = {};
            ansJson[qPartName]["type"] = ansType;
            ansJson[qPartName]["ans"] = [];

            // 
            // Make JSON as per ansType
            // 
            if (ansType === 'textbox') {
                var ansData = $(answers).val();
                if (ansData.trim()) {
                    ansJson[qPartName]["ans"].push(ansData);
                    $('#ansBkp' + qPartName).html(ansData);
                } else {
                    ansJson[qPartName]["ans"].push($('#ansBkp' + qPartName).html());
                }
            } else if (ansType === 'textarea') {
                ansData = CKEDITOR.instances[$(answers).attr('id')].getData();

                if (ansData.trim()) {
                    ansJson[qPartName]["ans"].push(ansData);
                    $('#ansBkp' + qPartName).html(ansData);
                } else {
                    ansJson[qPartName]["ans"].push($('#ansBkp' + qPartName).html());
                }
            } else if (ansType === 'choicebox') {
                $(answers).each(function (index, opt) {
                    if ($(opt).is(':checked')) {
                        ansJson[qPartName]["ans"].push($(opt).val());
                    }
                });
            } else if (ansType === 'canvas') {
                var canvasId = $(answers).attr('id');

                if (canvasElem[canvasId] !== undefined) {
                    var canvasId = $(answers).attr('id');
                    var canvasData = canvasElem[canvasId];
                    var canvasJson = canvasElem[canvasId].getSnapshotJSON();

                    if (canvasData.getImage()) {
                        var canvasUrl = canvasData.getImage().toDataURL();
                        ansJson[qPartName]["ans"].push({"canvasJson": canvasJson, "canvasUrl": canvasUrl});
                    }
                }
            } else {
                throw new Error("Unknown answer type " + ansType);
            }

        });

        ansJson["highlight-q"] = $('#highlight_q').is(':checked') ? 'yes' : 'no';

        $('.ans-sync-msg').show();

        $.ajax({
            type: "POST",
            url: baseURL + 'ajax/core/student/answer/update',
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
                var failMsg = "Failed to save your answer!\r\n\Please report to invigilator immediately. Don't refresh or close this page or you may loose your latest changes!\r\n";
                if (async) {
                    failMsg += "Error source:   \n------------------\n" + JSON.stringify(respJson) + "\r\n";
                    failMsg += "Answer recovery:\n------------------\nHit Ctrl+A and Ctrl+C in the text editor to copy your answer. Paste copied text into another text editor (i.e. notepad) and save to disk before reloading the page.";
                    alert(failMsg);
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

        var failMsg = "Something went wrong!\r\nPlease don't refresh/close web page window and contact your invigilator immediately.\r\n";
        failMsg += err.stack;

        if (async) {
            alert(failMsg);
        } else {
            return failMsg;
        }
    }
}

// 
// Create canvas for markup on background image.
// 
var canvasGetMarker = function (elementId, backgroundImage) {
    var painter = LC.init(
            document.getElementById(elementId),
            {
                backgroundShapes: [
                    LC.createShape(
                            'Image', {x: 0, y: 0, image: backgroundImage, scale: 1})
                ],
                defaultStrokeWidth: 5,
                primaryColor: 'red',
                secondaryColor: 'transparent',
                imageURLPrefix: baseURL + 'plugins/canvas/img',
                imageSize: {width: backgroundImage.width, height: backgroundImage.height}
            }
    );

    var fitWidth = true;
    var hzoom = painter.containerEl.children[0].height / backgroundImage.height;
    var wzoom = painter.containerEl.children[0].width / backgroundImage.width;

    if (fitWidth) {
        painter.setZoom(wzoom);
    } else {
        painter.setZoom(hzoom);
    }

    return painter;
}

// 
// Create canvas for drawing.
// 
var canvasGetDrawer = function (elementId) {
    var painter = LC.init(
            document.getElementById(elementId),
            {
                defaultStrokeWidth: 2,
                secondaryColor: 'transparent',
                imageURLPrefix: baseURL + 'plugins/canvas/img',
                imageSize: {width: null, height: null}
            }
    );

    return painter;
}

// 
// Setup canvas for simple drawing or markup on background image.
// 
var canvasSetup = function (elementId, canvasJson, backgroundImage) {
    // 
    // Get literally canvas painter:
    // 
    if (backgroundImage === undefined) {
        var painter = canvasGetDrawer(elementId);
    } else {
        var painter = canvasGetMarker(elementId, backgroundImage);

    }

    // 
    // Keep referens for later use during scheduled save:
    // 
    canvasElem[elementId] = painter;

    // 
    // Style paint application:
    // 
    var container = document.getElementById(elementId);

    container.style.backgroundColor = "white";
    container.style.border = "1px solid lightsteelblue";
    container.querySelector(".lc-picker").style.backgroundColor = "ghostwhite";
    container.querySelector(".lc-options").style.backgroundColor = "ghostwhite";
    container.querySelector(".lc-drawing").style.backgroundColor = "whitesmoke";

    // 
    // Local storage should always be most up to date. If missing and called
    // with canvas JSON data (from database), then load it instead.
    // 
    if (localStorage.getItem(elementId)) {
        painter.loadSnapshot(JSON.parse(localStorage.getItem(elementId)));
    } else if (canvasJson !== undefined) {
        painter.loadSnapshot(JSON.parse(canvasJson));
        localStorage.setItem(elementId, canvasJson);
    }

    // 
    // Set local storage on change for scheduled save to read back:
    // 
    painter.on('drawingChange', function () {
        dirtybit = 1;
        localStorage.setItem(elementId, JSON.stringify(painter.getSnapshot()));
    });
};

// 
// Initialize canvas (the drawing area).
// 
var canvasInit = function (elementId, canvasJson, imageUrl) {

    // 
    // Setup canvas for painting:
    // 
    if (!imageUrl) {
        canvasSetup(elementId, canvasJson);
        return;
    }

    // 
    // Setup canvas for markup on background image:
    // 
    var backgroundImage = new Image();
    backgroundImage.onload = function () {
        canvasSetup(elementId, canvasJson, this);
    };

    backgroundImage.src = imageUrl;
};

// 
// Event binding area.
// 
$(function () {

    $('#highlight_q').on('click', function () {
        if ($(this).is(':checked')) {
            $('#q' + qName + '_short > a').css('background-color', '#FEFF99');
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
            // 
            // Add spell check dialog:
            // 
            CKEDITOR.dialog.add('nativespellcheck', function (api) {

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
This addon uses the native spell check in the browser. Use \'&lt;ctrl&gt; + &lt;right click&gt;\' to <br/>\n\
access browser dictionaries and spelling suggestions.\n\
</p>\n\
<br/>\n\
<p>\n\
Click OK to toggle spell check as you type on/off for this text area. You can also the <br/>\n\
keyboard shortcut \'&lt;ctrl&gt; + &lt;alt&gt; + s\'.\n\
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
                        edt.execCommand('togglespellcheck');
                    }
                };

                return dialogDefinition;
            });

            // 
            // Add two commands: One for open dialog, the other for toggle spell check on/off:
            // 
            editor.addCommand("nativespellcheck", new CKEDITOR.dialogCommand('nativespellcheck'));
            editor.addCommand("togglespellcheck", {
                exec: function (edt) {
                    // 
                    // Get editor content node (not the textarea):
                    // 
                    var body = edt.document.getElementsByTag('body').getItem(0);
                    var enabled = false;

                    // 
                    // Get current spell check status:
                    // 
                    if (body.hasAttribute('spellcheck') === 'false') {
                        enabled = false;
                    } else if (body.getAttribute('spellcheck') === 'true') {
                        enabled = true;
                    } else {
                        enabled = false;
                    }

                    // 
                    // Toogle spell check on/off:
                    // 
                    body.setAttribute('spellcheck', !enabled);

                    // 
                    // Display auto-hiding notification:
                    // 
                    if (!enabled) {
                        edt.showNotification('Spellchecking is now enabled', 'success');
                    } else {
                        edt.showNotification('Spellchecking is now disabled', 'info');

                    }
                }
            });

            // 
            // Custom keyboard shortcuts ( ctrl + S ):
            // 
            editor.setKeystroke(CKEDITOR.ALT + CKEDITOR.CTRL + 83, 'togglespellcheck');
            editor.setKeystroke(CKEDITOR.ALT + CKEDITOR.CTRL + 115, 'togglespellcheck');

            // 
            // Append button to editing toolbar:
            // 
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
        zoomWindowOffetx: -30,
        borderColour: "black",
        cursor: "crosshair",
        scrollZoom: true,
        zoomWindowFadeIn: 500,
        zoomWindowFadeOut: 750,
        lensFadeIn: 200,
        lensFadeOut: 200
    });

    $('.img-zoom-inner').elevateZoom({
        responsive: true,
        zoomType: "window",
        zoomWindowPosition: 2,
        borderColour: "#dedede",
        cursor: "crosshair",
        scrollZoom: true,
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

    // 
    // Handle resource file display on/off:
    // 
    $('.resource-file').on('click', function () {
        $('.resource-file-box').toggle();
        if ($('.resource-file-box').is(':visible')) {
            $('.q-part-ans-area').addClass("col-sm-7").removeClass("col-sm-11");
            $(this).attr("title", "Hide resource files");
        } else {
            $('.q-part-ans-area').addClass("col-sm-11").removeClass("col-sm-7");
            $(this).attr("title", "Display resource files");
        }
    });
});
