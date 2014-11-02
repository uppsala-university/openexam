// JavaScript Document specific to View question
// @Author Ahsan Shahzad [MedfarmDoIT]


	/*======== var initialization ==========*/
	var dirtybit 	= 0;
	var syncEvery	= 30000; //30 seconds
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
	var syncAnswers = function (async) {
		
		var failed = false;
		if(ansId === 'None!')
			return;
		
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
				
				ansJson[qPartName]["ans"].push($(answers).val());
				
			} else if(ansType == 'textarea') {
				
				ansJson[qPartName]["ans"].push(CKEDITOR.instances[$(answers).attr('id')].getData());
				
			} else if(ansType == 'choicebox') {
				
				$(answers).each(function(index, opt) {
					if($(opt).is(':checked')) {
	                                        ansJson[qPartName]["ans"].push($(opt).val());
					}
                                });
				
			} else if(ansType == 'canvas') {
				
				var canvasId = $(answers).attr('id');
				var canvasJson = canvasData[canvasId];
				var canvasUrl = canvasElem[canvasId].getImage().toDataURL();
				ansJson[qPartName]["ans"].push({"canvasJson":canvasJson, "canvasUrl": canvasUrl});
				
			} else {
				ansJson[qPartName]["ans"].push(CKEDITOR.instances[$(answers).attr('id')].getData());
			}
			
                });
		
		ansJson["highlight-q"] = $('#highlight_q').is(':checked') ? 'yes' : 'no';
		
//		if(!failed) {
			$('.ans-sync-msg').show();
			
			// send ajax request to sync answers
			ajax(
				baseURL + 'core/ajax/student/answer/update',
				{"id":ansId, "answer":JSON.stringify(ansJson), "answered":"Y"}, 
				function(qData) {
					totalSyncs++;
					$('.ans-sync-msg').hide();
				},
				'POST',
				async
			);
			
/*		} else {
			alert("Unable to save your answer. \r\n \
			Please DO NOT refresh the page or close your browser otherwise your answer may lost. \r\n \
			Please report this issue to the exam invigilator immediately.");
		}*/
	}

	/*----------------------------------------------------------*/ 
	/*	initialize canvas elements			    */
	/*----------------------------------------------------------*/
	var initCanvas = function(elementId, canvasJson) {
		$('#'+elementId).literallycanvas({
			onInit: function(lc) {
				
				canvasElem[elementId] = lc;
				
				if(canvasJson != '') {
					lc.loadSnapshotJSON(canvasJson);
					canvasData[elementId] = canvasJson;
				}
				
				lc.on('drawingChange', function() {
					dirtybit = 1;
					canvasData[elementId] = lc.getSnapshotJSON();
				})
			},
			imageURLPrefix: baseURL + 'plugins/canvas/img', imageSize: {width: null, height: null}
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
		
		CKEDITOR.config.removeButtons = 'Link,Unlink';
                $('.ckeditor').each(function(index, element) {
			CKEDITOR.replace(element.id, {
				height: '100px'
			});
                });
		for (var i in CKEDITOR.instances) {
			CKEDITOR.instances[i].on('change', function() {dirtybit = 1;});
		}
		
		$(window).bind('beforeunload', function(){
		  	syncAnswers(false);
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
		
	});
//console.log("ALHAMDULILAH");
		
