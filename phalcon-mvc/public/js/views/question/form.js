// JavaScript Document specific to Exam create
// @Author Ahsan Shahzad [MedfarmDoIT]


		/*======== var initialization ==========*/
		var qPartTabs = $( "#qPartTabs" ).tabs();
		var tabTemplate = "<li><a href='#{href}'>#{label}</a> <span class='ui-icon ui-icon-close'>Remove Tab</span></li>";

		/*------------------------------------------*/ 
		/*	Events binding area						*/
		/*------------------------------------------*/
        $(document).ready(function() {
			
				/*======== Accordion ==========*/
				$( ".accordion" ).accordion({
					heightStyle: "content"
				});
				
				
				/*======== Removing the tab on click ==========*/
				qPartTabs.delegate( "span.ui-icon-close", "click", function() {
					var panelId = $( this ).closest( "li" ).remove().attr( "aria-controls" );
					$( "#" + panelId ).remove();
					qPartTabs.tabs( "refresh" );
					
					qPartTabs.find( ".ui-tabs-nav > li" ).each(function(index, element) {
						$(element).find('a').html("Part "+String.fromCharCode(96 + (index+1)));
                                        });
					tabCounter--;
					if(tabCounter == 2) {
						qPartTabs.find( "#q-parts-wrapper > .ui-tabs-panel" ).css('padding', '0px');
						$('#q-part-tabs').hide(200);
					}
				});

				/*======== answer type selector: input/textarea/drawingarea etc  ==========*/
				$('body').on('change', '.ans_type_selector', function() {
					$('.ans_type').hide();
					$(this).closest('.ans_type_selector_box_wrap').parent().find('.ans_type_selector').prop('checked', false);
					$(this).prop('checked', true);
					$(this).closest('.ans_type_selector_box_wrap').find('.ans_type').show();
				});

				/*======== Add resources to the question from Library dialog ==========*/
				$('body').on('click', '.add_media', function() {
					$.ajax({
							url: baseURL + 'utility/media/library',
							success: function(data) {
									$("#media_selector").html(data);
									$("#media_selector").dialog({
											autoOpen: true,
											width: "75%",
											modal: true,
											buttons: {
													"Add selected resources to question": function() {
															$('#selected-lib-img > li:visible > .title-box').each(function(index, itemTitle) {
																
																$('#'+$(".ui-tabs-active").attr("aria-controls")).find('.lib_resources_list').append('\
																	<li>\
																		<i class="fa fa-close"></i>\
																		<a href="#" file-path="'+$(this).attr('file-path')+'" target="_blank">'+$(itemTitle).find('input').val()+'</a>\
																	</li>'
																);
																
                                                            });
															
															$(this).dialog('destroy');
													},
													Cancel: function() {
															$(this).dialog('destroy');
													}
											},
											close: function() {
													//allFields.val( "" ).removeClass( "ui-state-error" );
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
					return false;
				});	

				/*========  add/del new sortable option in option type of questions ==========*/
                $('body').on('click', '.addNewSortable', function() {
                        $(this).closest('.choice_q').find('.question_opts').append(
							'<div style="padding-top:5px">\
								<img src="'+baseURL+'img/cross-circle.png" "class"="delopt hideable" "width"="10" "height"="10"> \
								<input type="checkbox"> \
								<span class="editabletext" default="Option - click to edit">Option - click to edit</span> \
							</div>');

                });
                $('body').on('click', '.delopt', function() {
                        $(this).parent().slideUp(500);
                });
				
				/*========  delete selected library resource ==========*/
                $('body').on('click', ".q_resources > ul > li > i", function() {
                        $(this).parent().remove();
                });
				
				
				/*========  Managing question correctors  ==========*/
				// prepare list
				var tmp = '<option value="">Choose a corrector for question</option>';
				$('.left-col-user').each(function(index, element) {
					if($(element).html() != '' && tmp.indexOf($(element).html()) < 0) {
						tmp += '<option value="'+$(element).attr('data-user')+'">' + $(element).html() + '</option>';
					}
                });
				// show list in openTip
                new Opentip('#uu-id4',
                        '<select class="q-correctors" isfor="uu-id4box"> ' + tmp + ' </select>',
                        {style: "drops", tipJoint: "top left"});
				// on corrector selection		
				$(document).on('change', '.q-correctors', function() {
						if($(this).val() != '' && $('.q_corrector_list').html().indexOf($(this).val()) < 0) {
							
							userName = $(this).val();
							cloned = $('.q_corrector_list > li:first').clone()
										.find('.left-col-user')
											.attr('data-user', userName)
											.html($(this).find('option').filter(':selected').text())
											.end();
							
							if(qId) {
								// send ajax request to add selected corrector in question
								ajax(
									baseURL + 'core/ajax/contributor/corrector/add', 
									{ "question_id":qId, "user":userName }, 
									function(status) {
										$('.q_corrector_list').append(cloned);
									}
								);
								
							} else {
								$('.q_corrector_list').append(cloned);
							}
							
						}
				});
				// on corrector delete
				$(document).on('click', '.del-corrector', function() {

						delCorrector = $(this);
						if($('.q_corrector_list').find('li').length > 1) {
							
							if(qId) {
								// send ajax request to delete selected corrector
								ajax(
									baseURL + 'core/ajax/contributor/corrector/delete', 
									{ "id":$(delCorrector).parent().find('span').attr('data-rec')}, 
									function(status) {
										$(delCorrector).parent().slideUp(500, function() {
												$(this).remove();
										});
									}
								);
								
							} else {
								$(this).parent().slideUp(500, function() {
										$(this).remove();
								});
							}
							
						} else {
							alert("A question must has atleast one corrector");
						}
				});


				/*========  Bind CKEDITORs ==========*/
				setTimeout(function(){
					
					for(var i=1; i < tabCounter; i++) {
						CKEDITOR.replace('q_text'+i, {
							height: '100px', 
							toolbar: [
								[ 'Cut', 'Copy', 'Paste', 'PasteFromWord', '-',  
								'Undo', 'Redo', 'Outdent','Indent', '-',
								'Bold','Italic','Underline', '-',
								'NumberedList','BulletedList'
								]
							]
						});
						
					}
					
				}, 100);
				CKEDITOR.config.autoParagraph = false;
        });


		/*------------------------------------------*/ 
		/*	Question parts management (Tabs)		*/
		/*------------------------------------------*/
		// add new question part
		var addQuestPartTab = function ()  {
			
			var qPartTitle = String.fromCharCode(96 + tabCounter);
			var label = "Part " + qPartTitle,
			id = "q-part-" + tabId,
			li = $( tabTemplate.replace( /#\{href\}/g, "#" + id ).replace( /#\{label\}/g, label ) ),
			tabContentHtml = qPartTabs.find( "#q-parts-wrapper > .ui-tabs-panel" ).filter(':first').html();
			qPartTabs.find( ".ui-tabs-nav" ).append( li );
			qPartTabs.find( "#q-parts-wrapper" ).append( "<div id='" + id + "' class='q-part'>" + tabContentHtml + "</div>" );

			//refresh  plugins on newly added content
			qPartTabs.tabs( "refresh" );	
			qPartTabs.tabs({ active: tabCounter-1 });
			$( ".accordion" ).accordion({heightStyle: "content"});
			//refreshCKEditor();
			
			if(tabCounter == 2) {
				qPartTabs.find( "#q-parts-wrapper > .ui-tabs-panel" ).css('padding', '1em 1.4em');
				$('#q-part-tabs').show(200);
			}
			tabId++;
			tabCounter++;
			
			//enable CkEditor
			$('#'+id).find('.write_q_ckeditor').attr('id', 'q_text'+tabId).val('');
			$('#'+id).find('.ans_type').hide();
			$('#'+id).find('#cke_q_text1').remove();
			CKEDITOR.replace('q_text'+tabId, {
					height: '100px', 
					toolbar: [
						[ 'Cut', 'Copy', 'Paste', 'PasteFromWord', '-',  
						'Undo', 'Redo', 'Outdent','Indent', '-',
						'Bold','Italic','Underline', '-',
						'NumberedList','BulletedList'
						]
					]
				} /*{
					height: '100px'}*/);
		}

		
//console.log("ALHAMDULILAH");
		
