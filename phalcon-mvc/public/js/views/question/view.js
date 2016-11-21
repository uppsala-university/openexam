// JavaScript Document specific to View question
// @Author Ahsan Shahzad [MedfarmDoIT]


	/*======== var initialization ==========*/
	var dirtybit 	= 0;
	var syncEvery	= 10000; //10 seconds 
	var ansJson	= {};
	var totalSyncs	= 0;
	var canvasData	= [];
	var canvasElem	= [];

	/*----------------------------------------------------------*/ 
	/*	Sync answers in database every 30 seconds	    */
	/*----------------------------------------------------------*/
	// autosave answers every 30 seconds if something has got changed
	setInterval(function() {
		if(dirtybit) {
			dirtybit = 0;
			syncAnswers(true);
		}
	}, syncEvery);
	// autosave sync function
	function syncAnswers(async, redirectToAfterSync) 
	{
		redirectToAfterSync = typeof redirectToAfterSync !== 'undefined' ? redirectToAfterSync : false;
		if(redirectToAfterSync && !dirtybit) {
			location.href=redirectToAfterSync;
			return;
		}
		
		try {
			var failed = false;
			if(ansId === 'None!') {
				if(!async) {
					return;
				} else {
					if(redirectToAfterSync) {
						location.href = redirectToAfterSync;
					}
				}
			}
			$('.q-part').each(function(index, qPart) {
				var qPartName = $(qPart).attr('data-id');
				var ansType = $(qPart).find('.q-part-ans-type').attr('data-id');
				var answers = $(qPart).find('.q-part-ans-type').find('.changeable');
				
				// initialize json obj attr
				ansJson[qPartName] = {};
				ansJson[qPartName]["type"] = ansType;
				ansJson[qPartName]["ans"] = [];
				
				// make json as per ansType
				if(ansType == 'textbox') {
					
					console.log("saving text box data");
					
					ansData = $(answers).val();
					//ansJson[qPartName]["ans"].push(ansData);
					if(ansData.trim()) {
						console.log("not empty:"+ansData);
						ansJson[qPartName]["ans"].push(ansData);
						$('#ansBkp'+qPartName).html(ansData);
					} else {
						console.log("its empty .. so sending old value:"+$('#ansBkp'+qPartName).html());
						ansJson[qPartName]["ans"].push($('#ansBkp'+qPartName).html());
					}
					
				} else if(ansType == 'textarea') {
					console.log("saving text area data");
					ansData = CKEDITOR.instances[$(answers).attr('id')].getData();
					//ansJson[qPartName]["ans"].push(ansData);
					if(ansData.trim()) {
						console.log("not empty:"+ansData);
						ansJson[qPartName]["ans"].push(ansData);
						$('#ansBkp'+qPartName).html(ansData);
					} else {
						console.log("its empty .. so sending old value:"+$('#ansBkp'+qPartName).html());
						ansJson[qPartName]["ans"].push($('#ansBkp'+qPartName).html());
					}
					
				} else if(ansType == 'choicebox') {
					
					$(answers).each(function(index, opt) {
						if($(opt).is(':checked')) {
							ansJson[qPartName]["ans"].push($(opt).val());
						}
					});
					
				} else if(ansType == 'canvas') {
					
					var canvasId = $(answers).attr('id');
					var canvasJson = canvasData[canvasId];
					
					if(typeof(canvasElem[canvasId]) != 'undefined' && canvasElem[canvasId].getImage()) {
						
						var canvasUrl = canvasElem[canvasId].getImage().toDataURL();
						ansJson[qPartName]["ans"].push({"canvasJson":canvasJson, "canvasUrl": canvasUrl});
						
/*						if(canvasUrl.trim()) {
							
							$('#ansBkp'+qPartName+'_json').val(canvasJson);
							$('#ansBkp'+qPartName+'_url').val(canvasUrl);
						} else {
							ansJson[qPartName]["ans"].push({"canvasJson":$('#ansBkp'+qPartName+'_json').val(), "canvasUrl": $('#ansBkp'+qPartName+'_url').val()});
						}*/
						
					} /*else {
						if (localStorage.getItem(elementId)) {
							lc.loadSnapshotJSON(localStorage.getItem(elementId));
							canvasData[elementId] = localStorage.getItem(elementId);
						}
						
						ansJson[qPartName]["ans"].push({"canvasJson":$('#ansBkp'+qPartName+'_json').val(), "canvasUrl": $('#ansBkp'+qPartName+'_url').val()});

					}*/
					
				} else {
					ansData = CKEDITOR.instances[$(answers).attr('id')].getData();
					ansJson[qPartName]["ans"].push(ansData);
					/*if(ansData.trim()) {
						ansJson[qPartName]["ans"].push(ansData);
						$('#ansBkp'+qPartName).val(ansData);
					} else {
						ansJson[qPartName]["ans"].push($('#ansBkp'+qPartName).val());
					}*/
				}
				
			});
			
			ansJson["highlight-q"] = $('#highlight_q').is(':checked') ? 'yes' : 'no';
			
	//		if(!failed) {
				$('.ans-sync-msg').show();
				
				failMsg = "Failed to save your answer! \r\n \
				Please report this issue to the exam invigilator immediately. \r\n \
				Please DO NOT refresh/close this web page otherwise we may loose your answer.\r\n";
				
				$.ajax({
					type: 	"POST",
					url: 	baseURL + 'core/ajax/student/answer/update',
					data: 	{"id":ansId, "answer":JSON.stringify(ansJson), "answered":1}, 
					async: 	async,
					dataType: "json",
					timeout: 5000,
					error: function(x, t, m) {
						if(t==="timeout") {
							alert("Seems like you lost your internet connection. Please make sure that internet cable is properly connected with computer. \r\n");
						} else {
							if(m != '' && m != null) {
								alert("Error occured! System was'nt able to save your answer during last 10 seconds. Please ignore if you see this message for the first time and review you answer again. Otherwise, please inform it to invigilator." + "\r\n\r\n >>>"+ JSON.stringify(x)+"--"+t+"--"+m);
							}
						}
					}
				})
				.done(function( respJson ) {
					
					if (typeof respJson.success == "undefined") {
						if(async) {
							alert(failMsg+JSON.stringify(respJson));
						} else {
							return failMsg;
						}
					} else {
						totalSyncs++;
						$('.ans-sync-msg').hide();
						if(redirectToAfterSync) {
							location.href = redirectToAfterSync;
						}
					}
				});			
				// send ajax request to sync answers
				/*ajax(
					baseURL + 'core/ajax/student/answer/update',
					{"id":ansId, "answer":JSON.stringify(ansJson), "answered":"Y"}, 
					function(qData) {
						totalSyncs++;
						$('.ans-sync-msg').hide();
					},
					'POST',
					async
				);*/
				
	/*		} else {
				alert("Unable to save your answer. \r\n \
				Please DO NOT refresh the page or close your browser otherwise your answer may lost. \r\n \
				Please report this issue to the exam invigilator immediately.");
			}*/
		} catch(err) {
			console.log(err);
			alert("Something went wrong in system. Please don't refresh/close web page window and contact your invigilator immediately."+JSON.stringify(err));
		}
	}

	/*----------------------------------------------------------*/ 
	/*	initialize canvas elements			    */
	/*----------------------------------------------------------*/
	var initCanvas = function(elementId, canvasJson) {
		$('#'+elementId).literallycanvas({
			onInit: function(lc) {

				canvasElem[elementId] = lc;
				
				if(localStorage.getItem(elementId) == canvasJson) {
					
					if (localStorage.getItem(elementId)) {
						lc.loadSnapshotJSON(localStorage.getItem(elementId));
						canvasData[elementId] = localStorage.getItem(elementId);
					}
					
				} else {
					
					if(localStorage.getItem(elementId) === null) {
						lc.loadSnapshotJSON(canvasJson);
						canvasData[elementId] = canvasJson;
					} else {
						dirtybit = 1;
						lc.loadSnapshotJSON(localStorage.getItem(elementId));
						canvasData[elementId] = localStorage.getItem(elementId);
					}
				}
				
				lc.on('drawingChange', function() {
					dirtybit = 1;
					canvasData[elementId] = lc.getSnapshotJSON();
					localStorage.setItem(elementId, lc.getSnapshotJSON());
				})
			},
			/*backgroundColor: 'whiteSmoke',*/
			defaultStrokeWidth: 2,
			secondaryColor: 'transparent',
			imageURLPrefix: baseURL + 'plugins/canvas/img', 
                        imageSize: {width: null, height: null}
		});		
	}
	

	/*------------------------------------------*/ 
	/*	Events binding area						*/
	/*------------------------------------------*/
	$(function () {
		
		$('#highlight_q').on('click', function() {
			if($(this).is(':checked')) {
				$('#q'+qName+'_short > a').css('background-color', '#FEFF99'); /*#FBC676*/
			} else {
				$('#q'+qName+'_short > a').css('background-color', '#FEFEFE');
			}
		});
		
		$(document).on('change', '.changeable', function() {
			dirtybit = 1;
		});
		
		$(document).on('click', '.logout-me', function() {
			if(confirm("Are you sure you want to logout from OpenExam?")) {
				syncAnswers(true, $(this).attr('hlink'));
			}
			return false;
		});
		
		$(document).on('click', '.sync-answer', function() {
			
			location.href = $(this).attr('hlink');
			
			//syncAnswers(true, $(this).attr('hlink'));
			/*console.log(syncSuccess);
			if(syncSuccess === true || syncSuccess === "test-mode") {
				console.log($(this).attr('hlink'));
				location.href = $(this).attr('hlink');
			}*/
			return false;
		});
		
		
		CKEDITOR.config.removeButtons = 'Link,Unlink';
                $('.ckeditor').each(function(index, element) {
                        var limit = element.getAttribute('word-count-limit');
                        if (limit === "") {
                            limit = -1;
                        }
			CKEDITOR.replace(element.id, {
				height: '100px',
                                wordcount: {
                                    countHTML: false,
                                    showWordCount: true,
                                    showCharCount: false,
                                    maxWordCount: limit
                                }			
                        });
                });
		for (var i in CKEDITOR.instances) {
			CKEDITOR.instances[i].on('change', function() {
                            dirtybit = 1;
                        });
		}
		
		$(window).bind('beforeunload', function(event){
		  	//event.preventDefault();
			return syncAnswers(false);
			//return 'Are you sure you want to leave?';
		});		

		$('.img-zoom').elevateZoom({
			responsive: true,
			zoomType: "window",
			zoomWindowPosition: 10,
			borderColour:"#dedede",
			cursor: "crosshair",
			scrollZoom:true,
			cursor:"pointer",
			zoomWindowFadeIn: 500,
			zoomWindowFadeOut: 750
		}); 
		
		$('.img-zoom-inner').elevateZoom({
			responsive: true,
			zoomType: "window",
			zoomWindowPosition: 2,
			borderColour:"#dedede",
			cursor: "crosshair",
			scrollZoom:true,
			cursor:"pointer",
			zoomWindowFadeIn: 500,
			zoomWindowFadeOut: 750
		}); 
		
		$(document).on('click','.zoom-in, .zoom-out', function() {
			return false;
		});
		
		// media plugin related settings and initializations
		$.fn.media.defaults.flvPlayer = baseURL + 'swf/mediaplayer.swf';
		$.fn.media.defaults.mp3Player = baseURL + 'swf/mediaplayer.swf';
		$('a.media').media();
		
		
		// handle question menu show/hide
		if($.cookie('qs-menu')) {
			$(document).trigger($.cookie('qs-menu'));
		}
	});
		
